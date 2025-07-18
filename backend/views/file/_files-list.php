<?php

use backend\assets\AppAsset as BackendAppAsset;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Events|null $event */
/** @var array $directories */

$this->registerJsFile('@web/js/modules/file/filesList.js', ['depends' => BackendAppAsset::class], 'filesList');

?>

<?php if ($dataProvider->totalCount): ?> 
<div class="p-3 d-flex flex-wrap gap-3 justify-content-end">
    <?= Html::button('<span><i class="ri-check-double-line fs-16 me-2"></i> Выбрать все</span>', ['class' => 'btn btn-primary btn-select-all-files']) ?>
    <?= Html::button('
        <div class="d-flex align-items-center cnt-text"><i class="ri-delete-bin-2-line align-middle fs-16 me-2"></i> Удалить</div>
        <div class="d-flex align-items-center d-none cnt-load">
            <span class="spinner-border flex-shrink-0" role="status"></span>
            <span class="flex-grow-1 ms-2">Удаление...</span>
        </div>
    ', ['class' => 'btn btn-danger btn-load btn-delete-selected-files', 'disabled' => true]) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative <?= ($dataProvider->totalCount ? 'border-bottom-0' : '') ?>">
        <h4 class="card-title mb-0 flex-grow-1">Файлы<?= ($event ? ". {$event?->expert->fullName}. {$event?->title}" : ''); ?></h4>
    </div>

    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'pager' => [
                'class' => \yii\bootstrap5\LinkPager::class,
                'listOptions' => [
                    'class' => 'pagination pagination-separated m-0',
                ],
                'maxButtonCount' => 5,
                'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
                'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>'
            ],
            'emptyText' => (is_null($event) ? 'Выберите событие.' : 'Ничего не найдено. Добавьте файлы.'),
            'emptyTextOptions' => [
                'class' => 'text-center',
            ],
            'tableOptions' => [
                'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
            ],
            'layout' => "
                <div class=\"table-responsive table-card table-responsive\">
                    <div>
                        {items}
                    </div>
                    ". ($dataProvider->totalCount 
                    ? 
                        "
                        <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 gridjs-pagination\">
                            <div class=\"text-body-secondary\">
                                {summary}
                            </div>
                            <div>
                                {pager}
                            </div>
                        </div>
                        "
                    : 
                        ''
                    )."
                </div>
            ",
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'files',

                    'header' => Html::checkBox('files_all', false, [
                        'class' => 'select-on-check-all form-check-input files-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'cssClass' => 'form-check-input files-check',

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'label' => 'Файл',
                    'format' => 'raw',
                    'content' => function ($model) {
                        $file = $model->path;
                        $fileSize = file_exists($file) ? filesize($file) : null;
                        return '<span class="fs-14 mb-1 h5">'. "{$model->name}.{$model->extension}" .'</span>' 
                                . (is_null($fileSize) ? '' : '<p class="fs-13 text-muted mb-0">' . Yii::$app->fileComponent->formatSizeUnits($fileSize)) . '</p>';
                    },
                    'options' => [
                        'class' => 'col-5'
                    ],
                    'contentOptions' => [
                        'class' => 'text-wrap text-break'
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'label' => 'Расположение',
                    'value' => function ($model) {
                        return $model->moduleTitle;
                    },
                    'options' => [
                        'class' => 'col-auto'
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '<div class="d-flex flex-wrap gap-2 justify-content-end">
                        {download}
                        {delete}
                    </div>',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::button('
                                <div class="d-flex align-items-center cnt-text"><i class="ri-delete-bin-2-line ri-lg"></i></div>
                                <div class="d-flex align-items-center d-none cnt-load"><span class="spinner-border flex-shrink-0" role="status"></span></div>
                            ', ['class' => 'btn btn-icon btn-danger btn-soft-danger btn-load btn-delete', 'data' => ['id' => $model->id]]);
                        },
                        'download' => function ($url, $model, $key) use ($event) {
                            return Html::a('<i class="ri-download-2-line ri-lg"></i>', ["file/download/{$model->id}"], ['class' => 'btn btn-icon btn-soft-secondary', 'data' => ['pjax' => 0], 'target' => '_blank', 'download' => true]);
                        },
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
            ],
        ]); ?>
    </div>
</div>

