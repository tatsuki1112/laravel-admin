<?php

namespace Encore\Admin\Form\Field;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Encore\Admin\Validator\CheckboxRequiredRule;

class MultipleSelect extends Select
{
    /**
     * Other key for many-to-many relation.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * Get other key for this many-to-many relation.
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getOtherKey()
    {
        if ($this->otherKey) {
            return $this->otherKey;
        }

        if (is_callable([$this->form->model(), $this->column]) &&
            ($relation = $this->form->model()->{$this->column}()) instanceof BelongsToMany
        ) {
            /* @var BelongsToMany $relation */
            $fullKey = $relation->getQualifiedRelatedPivotKeyName();
            $fullKeyArray = explode('.', $fullKey);

            return $this->otherKey = end($fullKeyArray);
        }

        throw new \Exception('Column of this field must be a `BelongsToMany` relation.');
    }

    /**
     * {@inheritdoc}
     */
    public function fill($data)
    {
        $this->data = $data;
        
        $relations = Arr::get($data, $this->column);

        if (is_string($relations)) {
            $this->value = explode(',', $relations);
        }

        if (!is_array($relations)) {
            return;
        }

        $first = current($relations);

        if (is_null($first)) {
            $this->value = null;

        // MultipleSelect value store as an ont-to-many relationship.
        } elseif (is_array($first)) {
            foreach ($relations as $relation) {
                $this->value[] = Arr::get($relation, "pivot.{$this->getOtherKey()}");
            }

            // MultipleSelect value store as a column.
        } else {
            $this->value = $relations;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginal($data)
    {
        $relations = Arr::get($data, $this->column);

        if (is_string($relations)) {
            $this->original = explode(',', $relations);
        }

        if (!is_array($relations)) {
            $this->original = null;
            return;
        }

        $first = current($relations);

        if (is_null($first)) {
            $this->original = null;

        // MultipleSelect value store as an ont-to-many relationship.
        } elseif (is_array($first)) {
            foreach ($relations as $relation) {
                $this->original[] = Arr::get($relation, "pivot.{$this->getOtherKey()}");
            }

            // MultipleSelect value store as a column.
        } else {
            $this->original = $relations;
        }
    }

    /**
     * Get field validation rules.
     */
    protected function getRules()
    {
        $rules = parent::getRules();

        // if contains required rule, set select option rule
        foreach($rules as $rule){
            if(is_string($rule) && $rule == 'required'){
                $rules[] = new CheckboxRequiredRule;
            }
        }

        return $rules;
    }

    public function prepare($value)
    {
        $value = (array) $value;

        /** @phpstan-ignore-next-line Parameter #2 $callback of function array_filter expects (callable(mixed): bool)|null, 'strlen_ex' given. */
        return array_filter($value, 'strlen_ex');
    }
}
