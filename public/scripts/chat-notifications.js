(() => {
  const badge = document.getElementById("chat-unread-badge");
  let unreadCount = Number(badge?.dataset.count || 0);
  const activeChatKey = location.pathname.endsWith("/chat.php")
    ? new URLSearchParams(location.search).get("chat") || "general"
    : null;
  let toastTimer;
  let polling = false;

  const toast = document.createElement("button");
  toast.type = "button";
  toast.className = "global-chat-notification";
  toast.hidden = true;
  toast.setAttribute("aria-live", "polite");
  document.body.appendChild(toast);

  toast.addEventListener("click", () => {
    if (toast.dataset.chatKey) location.href = `chat.php?chat=${encodeURIComponent(toast.dataset.chatKey)}`;
  });

  async function pollUnread() {
    if (polling) return;
    polling = true;
    try {
      const response = await fetch("services/chatPollProcess.php?action=unread", {
        headers: { Accept: "application/json" },
        cache: "no-store"
      });
      if (!response.ok) return;
      const data = await response.json();
      if (badge) {
        badge.dataset.count = String(data.unreadTotal);
        badge.textContent = data.unreadTotal > 99 ? "99+" : String(data.unreadTotal);
        badge.hidden = data.unreadTotal < 1;
      }
      if (data.unreadTotal > unreadCount && data.latest && data.latest.key !== activeChatKey) {
        toast.textContent = `Нове повідомлення від ${data.latest.sender}: ${data.latest.text}`;
        toast.dataset.chatKey = data.latest.key;
        toast.hidden = false;
        toast.classList.add("visible");
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
          toast.classList.remove("visible");
          setTimeout(() => { toast.hidden = true; }, 250);
        }, 6000);
      }
      unreadCount = data.unreadTotal;
    } catch (error) {
      // Next interval retries if chat service is unavailable.
    } finally {
      polling = false;
    }
  }

  pollUnread();
  window.setInterval(pollUnread, 5000);
})();
