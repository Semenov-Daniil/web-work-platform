<?php

use common\assets\ChoicesAsset;
use common\models\EncryptedPasswords;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\web\YiiAsset;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->registerJsFile('@web/js/modules/event/eventsList.js', ['depends' => [ChoicesAsset::class]], 'eventsList');

?>

<?php if ($dataProvider->totalCount): ?> 
<div class="p-3 d-flex flex-wrap gap-3 justify-content-end">
    <?= Html::button('<span><i class="ri-check-double-line align-middle fs-16 me-2"></i> Выбрать все</span>', ['class' => 'btn btn-primary btn-select-all-events']) ?>
    <?= Html::button('
        <div class="d-flex align-items-center cnt-text"><i class="ri-delete-bin-2-line align-middle fs-16 me-2"></i> Удалить</div>
        <div class="d-flex align-items-center d-none cnt-load">
            <span class="spinner-border flex-shrink-0" role="status"></span>
            <span class="flex-grow-1 ms-2">Удаление...</span>
        </div>
    ', ['class' => 'btn btn-danger btn-load btn-delete-selected-events', 'disabled' => true]) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">События</h4>
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
            'emptyText' => 'Ничего не найдено. Добавьте событие.',
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
                    'name' => 'events',

                    'header' => Html::checkbox('events_all', false, [
                        'class' => 'select-on-check-all form-check-input events-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'cssClass' => 'form-check-input events-check',

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'label' => 'Эксперт',
                    'value' => function ($model) {
                        return $model['expert'];
                    },
                    'options' => [
                        'class' => (Yii::$app->user->can('sExpert') ? 'col-3' : 'col-4')
                    ],
                    'visible' => (Yii::$app->user->can('sExpert') && $dataProvider->totalCount)
                ],
                [
                    'label' => 'Название',
                    'value' => function ($model) {
                        return $model['title'];
                    },
                    'options' => [
                        'class' => (Yii::$app->user->can('sExpert') ? 'col-3' : 'col-4')
                    ],
                    'visible' => $dataProvider->totalCount
                ],
                [
                    'label' => 'Кол-во модулей',
                    'value' => function ($model) {
                        return $model['countModules'];
                    },
                    'options' => [
                        'class' => 'col-1'
                    ],
                    'contentOptions' => [
                        'class' => 'text-center'
                    ],
                    'visible' => $dataProvider->totalCount
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            {update}
                            {delete}
                        </div>
                    ',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::button('
                                <div class="d-flex align-items-center cnt-text"><i class="ri-delete-bin-2-line ri-lg"></i></div>
                                <div class="d-flex align-items-center d-none cnt-load"><span class="spinner-border flex-shrink-0" role="status"></span></div>
                            ', ['class' => 'btn btn-icon btn-danger btn-soft-danger btn-load btn-delete', 'data' => ['id' => $model['id']]]);
                        },
                        'update' => function ($url, $model, $key) {
                            return Html::button('<i class="ri-pencil-line ri-lg"></i>', ['class' => 'btn btn-icon btn-soft-info btn-update', 'data' => ['id' => $model['id']]]);
                        },
                    ],
                    'visible' => $dataProvider->totalCount
                ],
            ],
        ]); ?>
    </div>
</div>

