<!doctype html>
<html lang="uk">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="src/mars-reklama.com.ua-favicon.ico" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="styles/general-style.css" />
  <link rel="stylesheet" href="styles/table-page-style.css" />
  <link rel="stylesheet" href="styles/search-bar-style.css" />
  <link rel="stylesheet" href="styles/page-header-style.css" />
  <link rel="stylesheet" href="styles/nav-bar-style.css" />
  <title>Постачання</title>
</head>

<body>
  <header class="main-header">
    <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
    <div class="search-bar-container">
      <select name="income-search-select" id="income-search-select" class="search-select">
        <option value="supplier-name">Постачальник</option>
        <option value="income-date">Дата</option>
        <option value="income-material">Матеріал</option>
      </select>
      <input type="text" name="income-search" id="income-search" class="search-bar" placeholder="Пошук" />
      <button class="search-button">
        <img src="src/search.png" alt="search-button" draggable="false" />
      </button>
    </div>

    <div class="add-new-item" id="add-new-income">+</div>

    <div class="header-right-corner">
      <p id="main-greetings">Вітаю, username!</p>
    </div>
  </header>

  <div class="nav-bar">
    <div class="left-arrow">
      <img src="src/left-arrow.svg" alt="left-arrow" class="nav-bar-arrow" />
    </div>
    <div class="mid-section">Поточний тиждень</div>
    <div class="right-arrow">
      <img src="src/right-arrow.svg" alt="left-arrow" class="nav-bar-arrow" />
    </div>
  </div>

  <main class="main-body">
    <section class="table">
      <div class="table-header">28.08.2026</div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">1</p>
          <img src="src/plastics.svg" alt="plastics" class="supplier-logo" draggable="false" />
          <p class="income-position">Плівка Oracal</p>
          <p class="income-position">640</p>
          <p class="income-position">білий</p>
          <p class="income-position">глянець</p>
          <p class="income-position">100 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">2</p>
          <img src="src/Orly.png" alt="orly" class="supplier-logo" draggable="false" />
          <p class="income-position">Сіті</p>
          <p class="income-position">економ</p>
          <p class="income-position">125 см</p>
          <p class="income-position">150 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x4</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">3</p>
          <img src="src/ARB.png" alt="arb" class="supplier-logo" draggable="false" />
          <p class="income-position">Блокплівка ARB</p>
          <p class="income-position">106 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">4</p>
          <img src="src/prostir.webp" alt="prostir" class="supplier-logo" draggable="false" />
          <p class="income-position">Банер</p>
          <p class="income-position">литий</p>
          <p class="income-position">160 см</p>
          <p class="income-position">440 г/м2</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
    </section>
    <section class="table">
      <div class="table-header">27.08.2026</div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">1</p>
          <img src="src/plastics.svg" alt="plastics" class="supplier-logo" draggable="false" />
          <p class="income-position">Плівка Oracal</p>
          <p class="income-position">640</p>
          <p class="income-position">білий</p>
          <p class="income-position">глянець</p>
          <p class="income-position">100 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">2</p>
          <img src="src/Orly.png" alt="orly" class="supplier-logo" draggable="false" />
          <p class="income-position">Сіті</p>
          <p class="income-position">економ</p>
          <p class="income-position">125 см</p>
          <p class="income-position">150 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x4</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">3</p>
          <img src="src/ARB.png" alt="arb" class="supplier-logo" draggable="false" />
          <p class="income-position">Блокплівка ARB</p>
          <p class="income-position">106 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">4</p>
          <img src="src/prostir.webp" alt="prostir" class="supplier-logo" draggable="false" />
          <p class="income-position">Банер</p>
          <p class="income-position">литий</p>
          <p class="income-position">160 см</p>
          <p class="income-position">440 г/м2</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
    </section>
    <section class="table">
      <div class="table-header">26.08.2026</div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">1</p>
          <img src="src/plastics.svg" alt="plastics" class="supplier-logo" draggable="false" />
          <p class="income-position">Плівка Oracal</p>
          <p class="income-position">640</p>
          <p class="income-position">білий</p>
          <p class="income-position">глянець</p>
          <p class="income-position">100 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">2</p>
          <img src="src/Orly.png" alt="orly" class="supplier-logo" draggable="false" />
          <p class="income-position">Сіті</p>
          <p class="income-position">економ</p>
          <p class="income-position">125 см</p>
          <p class="income-position">150 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x4</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">3</p>
          <img src="src/ARB.png" alt="arb" class="supplier-logo" draggable="false" />
          <p class="income-position">Блокплівка ARB</p>
          <p class="income-position">106 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">4</p>
          <img src="src/prostir.webp" alt="prostir" class="supplier-logo" draggable="false" />
          <p class="income-position">Банер</p>
          <p class="income-position">литий</p>
          <p class="income-position">160 см</p>
          <p class="income-position">440 г/м2</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
    </section>
    <section class="table">
      <div class="table-header">25.08.2026</div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">1</p>
          <img src="src/plastics.svg" alt="plastics" class="supplier-logo" draggable="false" />
          <p class="income-position">Плівка Oracal</p>
          <p class="income-position">640</p>
          <p class="income-position">білий</p>
          <p class="income-position">глянець</p>
          <p class="income-position">100 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">2</p>
          <img src="src/Orly.png" alt="orly" class="supplier-logo" draggable="false" />
          <p class="income-position">Сіті</p>
          <p class="income-position">економ</p>
          <p class="income-position">125 см</p>
          <p class="income-position">150 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x4</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">3</p>
          <img src="src/ARB.png" alt="arb" class="supplier-logo" draggable="false" />
          <p class="income-position">Блокплівка ARB</p>
          <p class="income-position">106 см</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
      <hr />
      <div class="table-element">
        <div class="left-side">
          <p class="income-number">4</p>
          <img src="src/prostir.webp" alt="prostir" class="supplier-logo" draggable="false" />
          <p class="income-position">Банер</p>
          <p class="income-position">литий</p>
          <p class="income-position">160 см</p>
          <p class="income-position">440 г/м2</p>
          <p class="income-position">50 м</p>
        </div>
        <div class="right-side">
          <p class="income-count">x1</p>
        </div>
      </div>
    </section>
  </main>

  <script>
    const marsLogo = document.getElementById("header-logo");

    marsLogo.addEventListener("click", () => {
      window.location.href = "main.php";
    });
  </script>
</body>

</html>