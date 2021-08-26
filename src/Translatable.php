<?php

namespace Pharaonic\Laravel\Translatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Pharaonic\Laravel\Translatable\Traits\Actions;
use Pharaonic\Laravel\Translatable\Traits\Scopes;

trait Translatable
{
    use Scopes, Actions;

    /**
     * Current Locale
     *
     * @var string|null
     */
    protected $currentTranslatableLocale = null;

    /**
     * Init Translatable
     *
     * @return void
     */
    public function initializeTranslatable()
    {
        $this->currentTranslatableLocale = app()->getLocale();
    }

    /**
     * Boot Translatable (retrieved / saved / deleting)
     *
     * @return void
     */
    public static function bootTranslatable()
    {
        // GET
        static::retrieved(function (Model $model) {
            if (isset($model->translatableAttributes))
                foreach ($model->translatableAttributes as $attr)
                    $model->setAttribute($attr, $model->translateOrDefault()->{$attr} ?? null);
        });

        // STORE/UPDATE
        static::saving(function (Model $model) {
            foreach ($model->translatableAttributes as $attr)
                if ($model->{$attr} ?? null) unset($model->{$attr});
        });

        // SAVED
        static::saved(function (Model $model) {
            $model->saveTranslations();
        });
        
        // DESTROY
        static::deleting(function (Model $model) {
            $model->deleteTranslations();
        });
    }

    /**
     * Get Translatable table's name
     *
     * @return string
     */
    public function getTranslatableTable()
    {
        return Str::snake(Str::pluralStudly(class_basename($this) . 'Translation'));
    }

    /**
     * Get Translatable model's name
     *
     * @return string
     */
    public function getTranslatableModel()
    {
        return __CLASS__ . 'Translation';
    }

    /**
     * Get Translatable model's field
     *
     * @return string
     */
    public function getTranslatableField()
    {
        return Str::snake(Str::studly(class_basename($this))) . '_' . $this->getKeyName();
    }

    /**
     * Get Prepared Traslations
     *
     * @return Collection
     */
    protected function preparedTranslations()
    {
        if (isset($this->getRelation('translations')[0])) {
            $list = $this->getRelation('translations')->keyBy('locale');
            $this->setRelation('translations', $list);
            return $list;
        }

        return $this->getRelation('translations');
    }

    /**
     * Getting Translations
     *
     * @return Collection
     */
    public function getTranslationsAttribute()
    {
        if ($this->relationLoaded('translations'))
            return $this->preparedTranslations();

        $list = $this->translations()->get()->keyBy('locale');
        $this->setRelation('translations', $list);
        return $list;
    }

    /**
     * Save All Dirty Translations
     *
     * @return void
     */
    protected function saveTranslations()
    {
        if ($this->relationLoaded('translations'))
            foreach ($this->getRelation('translations') as $item)
                if ($item->isDirty())
                    $item->save();
    }

    /**
     * Delete All Translations
     *
     * @return void
     */
    protected function deleteTranslations()
    {
        $this->translations()->delete();
    }
}
