<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "statuses".
 *
 * @property int $id
 * @property string $title
 *
 * @property Users[] $users
 * @property Events[] $events
 */
class Statuses extends \yii\db\ActiveRecord
{
    const CONFIGURING = 'configuring';
    const READY = 'ready';
    const DELETING = 'deleting';
    const ERROR = 'error';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%statuses}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(Users::class, ['statuses_id' => 'id']);
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Events::class, ['statuses_id' => 'id']);
    }

    public static function getStatusId(string $title): ?int
    {
        $cacheKey = 'status_id_' . $title;
        return Yii::$app->cache->getOrSet($cacheKey, function () use ($title) {
            return self::findOne(['title' => $title])?->id;
        }, 3600);
    }
}
