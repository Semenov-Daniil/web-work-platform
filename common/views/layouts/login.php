<?php

/** @var yii\web\View $this */
/** @var string $content */

use common\assets\AppAsset;
use common\assets\LoginAsset;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\web\YiiAsset;

LoginAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.png')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="material" data-theme-colors="default" data-layout="semibox" data-topbar="light" data-layout-position="fixed" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg"
data-sidebar-image="none" data-preloader="disable" data-bs-theme="light" class="h-100">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body class="d-flex flex-column h-100">
    <?php $this->beginBody() ?>

    <main class="auth-page-wrapper d-flex flex-shrink-0 flex-fill">
        <div class="d-flex flex-fill justify-content-center align-items-center">
            <div class="container">
                <div class="row justify-content-center">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </main>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>