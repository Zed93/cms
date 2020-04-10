<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally'), ['isOpen' => false]); ?>

<?/* if ($model->is_active) : */?>
    <?= $form->field($model, 'published_at')->widget(\skeeks\cms\backend\widgets\forms\DateControlInputWidget::class, [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \skeeks\cms\backend\widgets\forms\DateControlInputWidget::FORMAT_DATETIME,
    ]); ?>

    <?= $form->field($model, 'published_to')->widget(\skeeks\cms\backend\widgets\forms\DateControlInputWidget::class, [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \skeeks\cms\backend\widgets\forms\DateControlInputWidget::FORMAT_DATETIME,
    ]); ?>

<?/* endif; */?>

<? if ($contentModel->is_have_page) : ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 255])->hint(\Yii::t('skeeks/cms',
        "This parameter affects the address of the page")); ?>
<? endif; ?>

<?= $form->field($model, 'priority')->widget(\skeeks\cms\backend\widgets\forms\NumberInputWidget::class); ?>

<?= $form->field($model, 'fileIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'multiple' => true,
    ]
); ?>

<?php if ($contentModel->parent_content_id) : ?>
    <?= $form->field($model, 'parent_content_element_id')->widget(
        \skeeks\cms\backend\widgets\SelectModelDialogContentElementWidget::class,
        [
            'content_id' => $contentModel->parent_content_id,
        ]
    )->label($contentModel->parentContent->name_one) ?>
<?php endif; ?>

<?= $form->field($model, 'external_id'); ?>


<? $fieldSet::end(); ?>
