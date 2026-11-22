<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../services/databaseConnect.php';

function materialText($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$allMaterials = $materialsCollection->find([], ['sort' => ['name' => 1, 'createdAt' => 1]])->toArray();
$parameterNames = [];
foreach ($allMaterials as $material) {
    foreach (($material['parameters'] ?? []) as $parameter) {
        $name = trim((string) ($parameter['name'] ?? ''));
        if ($name !== '') $parameterNames[$name] = $name;
    }
}
natcasesort($parameterNames);

$selectedField = (string) ($_GET['field'] ?? 'name');
$search = trim((string) ($_GET['search'] ?? ''));
if ($selectedField !== 'name' && $selectedField !== 'type' && !isset($parameterNames[substr($selectedField, 6)])) $selectedField = 'name';
$contains = static function ($value, string $needle): bool {
    $value = (string) $value;
    return function_exists('mb_stripos') ? mb_stripos($value, $needle, 0, 'UTF-8') !== false : stripos($value, $needle) !== false;
};

$materialsByType = ['roll' => [], 'sheet' => []];
$hasMaterials = false;
foreach ($allMaterials as $material) {
    $type = $material['type'] ?? '';
    if (!isset($materialsByType[$type])) continue;
    $matches = $search === '';
    if ($search !== '') {
        if ($selectedField === 'name') {
            $matches = $contains($material['name'] ?? '', $search);
        } elseif ($selectedField === 'type') {
            $matches = $contains($type === 'roll' ? 'Рулонний' : 'Листовий', $search);
        } else {
            $parameterName = substr($selectedField, 6);
            foreach (($material['parameters'] ?? []) as $parameter) {
                if (($parameter['name'] ?? '') !== $parameterName) continue;
                foreach (($parameter['values'] ?? []) as $value) {
                    if ($contains($value, $search)) { $matches = true; break 2; }
                }
            }
        }
    }
    if (!$matches) continue;
    $materialsByType[$type][(string) ($material['name'] ?? '')][] = $material;
    $hasMaterials = true;
}

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
    <title>Матеріали</title>
</head>

<body>
    <form action="" method="get">
        <div class="main-header">
            <img src="src/logo.jpg" alt="mars-logo" id="header-logo" draggable="false" />
            <div class="search-bar-container">
                <select name="field" id="supplier-search-select" class="search-select">
                    <option value="name" <?= $selectedField === 'name' ? 'selected' : '' ?>>Матеріал</option>
                    <option value="type" <?= $selectedField === 'type' ? 'selected' : '' ?>>Тип</option>
                    <?php foreach ($parameterNames as $parameterName): ?>
                        <option value="param:<?= materialText($parameterName) ?>" <?= $selectedField === 'param:' . $parameterName ? 'selected' : '' ?>><?= materialText($parameterName) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" id="supplier-search" class="search-bar" placeholder="Пошук" value="<?= materialText($search) ?>">
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

    <?php if (false): ?>
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
                    <div class="multiple-params">
                        <p class="material-position">110 см</p>
                        <p class="material-position">137 см</p>
                        <p class="material-position">160 см</p>
                        <p class="material-position">220 см</p>
                        <p class="material-position">250 см</p>
                        <p class="material-position">320 см</p>
                    </div>
                    <p class="material-position">440 г/м2</p>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">2</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">сітка</p>
                    <div class="multiple-params">
                        <p class="material-position">220 см</p>
                        <p class="material-position">320 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">3</p>
                    <p class="material-position">Банер</p>
                    <p class="material-position">текстильний блокуючий</p>
                    <div class="multiple-params">
                        <p class="material-position">320 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
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
                    <div class="multiple-params">
                        <p class="material-position">Glossy</p>
                        <p class="material-position">Matte</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">000</p>
                        <p class="material-position">010</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">100 см</p>
                        <p class="material-position">126 см</p>
                        <p class="material-position">140 см</p>
                        <p class="material-position">160 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">2</p>
                    <p class="material-position">Плівка Oracal</p>
                    <p class="material-position">641</p>
                    <div class="multiple-params">
                        <p class="material-position">Glossy</p>
                        <p class="material-position">Matte</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">000</p>
                        <p class="material-position">010</p>
                        <p class="material-position">020</p>
                        <p class="material-position">019</p>
                        <p class="material-position">021</p>
                        <p class="material-position">022</p>
                        <p class="material-position">025</p>
                        <p class="material-position">312</p>
                        <p class="material-position">030</p>
                        <p class="material-position">031</p>
                        <p class="material-position">032</p>
                        <p class="material-position">047</p>
                        <p class="material-position">034</p>
                        <p class="material-position">036</p>
                        <p class="material-position">035</p>
                        <p class="material-position">404</p>
                        <p class="material-position">040</p>
                        <p class="material-position">043</p>
                        <p class="material-position">042</p>
                        <p class="material-position">041</p>
                        <p class="material-position">045</p>
                        <p class="material-position">562</p>
                        <p class="material-position">518</p>
                        <p class="material-position">050</p>
                        <p class="material-position">065</p>
                        <p class="material-position">049</p>
                        <p class="material-position">086</p>
                        <p class="material-position">067</p>
                        <p class="material-position">057</p>
                        <p class="material-position">051</p>
                        <p class="material-position">098</p>
                        <p class="material-position">052</p>
                        <p class="material-position">084</p>
                        <p class="material-position">053</p>
                        <p class="material-position">056</p>
                        <p class="material-position">066</p>
                        <p class="material-position">054</p>
                        <p class="material-position">055</p>
                        <p class="material-position">060</p>
                        <p class="material-position">613</p>
                        <p class="material-position">061</p>
                        <p class="material-position">068</p>
                        <p class="material-position">062</p>
                        <p class="material-position">064</p>
                        <p class="material-position">063</p>
                        <p class="material-position">800</p>
                        <p class="material-position">083</p>
                        <p class="material-position">081</p>
                        <p class="material-position">082</p>
                        <p class="material-position">023</p>
                        <p class="material-position">070</p>
                        <p class="material-position">073</p>
                        <p class="material-position">071</p>
                        <p class="material-position">076</p>
                        <p class="material-position">074</p>
                        <p class="material-position">072</p>
                        <p class="material-position">090</p>
                        <p class="material-position">091</p>
                        <p class="material-position">092</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">100 см</p>
                        <p class="material-position">126 см</p>
                        <p class="material-position">140 см</p>
                        <p class="material-position">160 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Плівка Китай</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Плівка Китай</p>
                    <div class="multiple-params">
                        <p class="material-position">Glossy</p>
                        <p class="material-position">Matte</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">White</p>
                        <p class="material-position">Transparent</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">107 см</p>
                        <p class="material-position">127 см</p>
                        <p class="material-position">137 см</p>
                        <p class="material-position">152 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Блокплівка ARB</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Блокплівка ARB</p>
                    <div class="multiple-params">
                        <p class="material-position">106 см</p>
                        <p class="material-position">127 см</p>
                        <p class="material-position">137 см</p>
                        <p class="material-position">152 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Перфоплівка</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Перфоплівка</p>
                    <div class="multiple-params">
                        <p class="material-position">98 см</p>
                        <p class="material-position">106 см</p>
                        <p class="material-position">127 см</p>
                        <p class="material-position">137 см</p>
                        <p class="material-position">152 см</p>
                    </div>
                    <p class="material-position">50 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Сіті</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Сіті</p>
                    <p class="material-position">економ</p>
                    <div class="multiple-params">
                        <p class="material-position">125 см</p>
                        <p class="material-position">155 см</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">150 м</p>
                        <p class="material-position">300 м</p>
                    </div>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">2</p>
                    <p class="material-position">Сіті</p>
                    <p class="material-position">преміум</p>
                    <div class="multiple-params">
                        <p class="material-position">53 см</p>
                        <p class="material-position">91 см</p>
                        <p class="material-position">120 см</p>
                        <p class="material-position">127 см</p>
                        <p class="material-position">160 см</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">150 г/м2</p>
                        <p class="material-position">200 г/м2</p>
                    </div>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Блюбек</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Блюбек</p>
                    <div class="multiple-params">
                        <p class="material-position">125 см</p>
                        <p class="material-position">155 см</p>
                        <p class="material-position">160 см</p>
                    </div>
                    <p class="material-position">300 м</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">Холст</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Холст</p>
                    <div class="multiple-params">
                        <p class="material-position">107 см</p>
                        <p class="material-position">127 см</p>
                        <p class="material-position">152 см</p>
                    </div>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <p class="material-type">Листові матеріали</p>
        <section class="table">
            <div class="table-header">Акрил</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">Акрил</p>
                    <div class="multiple-params">
                        <p class="material-position">Прозорий</p>
                        <p class="material-position">Білий</p>
                        <p class="material-position">Молочний</p>
                        <p class="material-position">Дзеркальний золотий</p>
                        <p class="material-position">Дзеркальний срібний</p>
                        <p class="material-position">Чорний</p>
                        <p class="material-position">Червоний</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">1,5 мм</p>
                        <p class="material-position">1,7 мм</p>
                        <p class="material-position">2 мм</p>
                        <p class="material-position">3 мм</p>
                        <p class="material-position">4 мм</p>
                        <p class="material-position">5 мм</p>
                        <p class="material-position">6 мм</p>
                    </div>
                    <p class="material-position">2050 х 3050 мм</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">ПВХ</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">ПВХ</p>
                    <div class="multiple-params">
                        <p class="material-position">2 мм</p>
                        <p class="material-position">3 мм</p>
                        <p class="material-position">4 мм</p>
                        <p class="material-position">5 мм</p>
                    </div>
                    <div class="multiple-params">
                        <p class="material-position">2050 х 3050 мм</p>
                        <p class="material-position">1220 х 3050 мм</p>
                    </div>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">ХДФ</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">ХДФ</p>
                    <p class="material-position">2,5 мм</p>
                    <p class="material-position">2070 х 2850 мм</p>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>
        <section class="table">
            <div class="table-header">АКП</div>
            <hr />
            <div class="table-element">
                <div class="left-side">
                    <p class="material-number">1</p>
                    <p class="material-position">АКП</p>
                    <p class="material-position">3 мм</p>
                    <div class="multiple-params">
                        <p class="material-position">1222 х 2440 мм</p>
                        <p class="material-position">1250 х 3050 мм</p>
                        <p class="material-position">1250 х 5800 мм</p>
                        <p class="material-position">1500 х 3050 мм</p>
                    </div>
                </div>
                <div class="right-side">
                    <p class="material-edit">Редагувати</p>
                </div>
            </div>
        </section>

    </main>
    <?php endif; ?>

    <main class="main-body">
        <?php if (!$hasMaterials): ?>
            <p class="none-message">Не було знайдено жодного матеріалу!</p>
        <?php else: ?>
        <?php foreach (['roll' => 'Рулонні матеріали', 'sheet' => 'Листові матеріали'] as $type => $title): ?>
            <?php if (empty($materialsByType[$type])) continue; ?>
            <p class="material-type"><?= $title ?></p>
            <?php foreach ($materialsByType[$type] as $name => $materials): ?>
                <section class="table">
                    <div class="table-header"><?= materialText($name) ?></div>
                    <?php foreach ($materials as $number => $material): ?>
                        <?php if ($number > 0): ?><hr /><?php endif; ?>
                        <div class="table-element">
                            <div class="left-side">
                                <p class="material-number"><?= $number + 1 ?></p>
                                <p class="material-position"><?= materialText($material['name'] ?? '') ?></p>
                                <?php foreach (($material['parameters'] ?? []) as $parameter): ?>
                                    <?php $values = $parameter['values'] ?? []; ?>
                                    <?php if (count($values) === 1): ?>
                                        <p class="material-position" title="<?= materialText($parameter['name'] ?? '') ?>"><?= materialText($values[0]) ?></p>
                                    <?php elseif (count($values) > 1): ?>
                                        <div class="multiple-params" title="<?= materialText($parameter['name'] ?? '') ?>">
                                            <?php foreach ($values as $value): ?><p class="material-position"><?= materialText($value) ?></p><?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="right-side"><a class="material-edit" href="editMaterial.php?id=<?= urlencode((string) $material['_id']) ?>">Редагувати</a></div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
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
