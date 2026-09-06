<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../services/databaseConnect.php';

?>

<!DOCTYPE html>
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
    <title>Склад</title>
</head>

<body>
    <form action="" method="get">
        <div class="main-header">
            <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
            <div class="search-bar-container">
                <select name="field" id="supplier-search-select" class="search-select">
                    <option value="">Матеріал</option>
                    <option value="">Тип</option>
                    <option value="">Серія</option>
                    <option value="">Покриття</option>
                    <option value="">Колір</option>
                    <option value="">Щільність</option>
                    <option value="">Розмір</option>
                    <option value="">Довжина</option>
                </select>
                <input type="text" name="search" id="supplier-search" class="search-bar" placeholder="Пошук">
                <button class="search-button">
                    <img src="src/search.png" alt="search-button" draggable="false" />
                </button>

            </div>
            <div class="add-new-item" id="add-new-supplier">+</div>

            <div class="header-right-corner">
                <p id="main-greetings">Вітаю,
                    <?= htmlspecialchars($_SESSION["username"]) ?>!
                </p>
            </div>
        </div>

    </form>

    <main class="main-body">
        <p class="material-type">Рулонні матеріали</p>
        <section class="table">
            <div class="table-header">Банер</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">110 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">100%</p>
                        <p class="material-position">100%</p>
                        <p class="material-position">40%</p>
                        <p class="material-position">10%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:green">В наявності</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">2</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">137 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">100%</p>
                        <p class="material-position">100%</p>
                        <p class="material-position">20%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:green">В наявності</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">3</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">160 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">90%</p>
                        <p class="material-position">10%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:green">В наявності</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">4</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">220 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">100%</p>
                        <p class="material-position">80%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:green">В наявності</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">5</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">250 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">50%</p>
                        <p class="material-position">15%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:orange">До закупки</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">6</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">литий</p>
                    <p class="material-position">320 см</p>
                    <p class="material-position">440 г/м2</p>
                    <div class="multiple-params">
                        <p class="material-position">20%</p>
                    </div>

                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:red">Терміново</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Плівка Oracal</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Плівка Oracal</p>
                    <p class="material-position">640</p>
                    <p class="material-position">Glossy</p>
                    <p class="material-position">010</p>
                    <p class="material-position">100 см</p>
                    <div class="multiple-params">
                        <p class="material-position">40%</p>
                    </div>
                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:orange">До закупки</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr>
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">2</p>
                    <p class="material-position">Плівка Oracal</p>
                    <p class="material-position">640</p>
                    <p class="material-position">Glossy</p>
                    <p class="material-position">010</p>
                    <p class="material-position">126 см</p>
                    <div class="multiple-params">
                        <p class="material-position">100%</p>
                        <p class="material-position">15%</p>
                        <p class="material-position">5%</p>
                    </div>
                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:green">В наявності</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr>
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">3</p>
                    <p class="material-position">Плівка Oracal</p>
                    <p class="material-position">640</p>
                    <p class="material-position">Glossy</p>
                    <p class="material-position">010</p>
                    <p class="material-position">140 см</p>
                    <div class="multiple-params">
                        <p class="material-position">25%</p>
                        <p class="material-position">5%</p>
                    </div>
                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:orange">До закупки</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
            <hr>
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">4</p>
                    <p class="material-position">Плівка Oracal</p>
                    <p class="material-position">640</p>
                    <p class="material-position">Glossy</p>
                    <p class="material-position">010</p>
                    <p class="material-position">160 см</p>
                    <div class="multiple-params">
                        <p class="material-position">25%</p>
                    </div>
                </div>
                <div class="mid-side">
                    <p>Статус: <span class="stock-status" style="color:orange">До закупки</span></p>
                </div>
                <div class="right-side">
                    <p class="stock-edit">Редагувати</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        const marsLogo = document.getElementById("header-logo");
        const addButton = document.getElementById("add-new-supplier");
        // const editButtons = document.querySelectorAll(".flex-element-edit");

        // editButtons.forEach(button => {
        //     button.addEventListener("click", function () {
        //         const supplierId = this.dataset.id;
        //         window.location.href = `editMaterial.php?id=${materialId}`;
        //     });
        // });

        marsLogo.addEventListener("click", () => {
            window.location.href = "main.php";
        });

        addButton.addEventListener("click", () => {
            window.location.href = "addMaterial.php";
        });
    </script>
</body>

</html>