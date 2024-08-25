<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;

class Text extends Field
{
    use PlainInput;

    /**
     * @var string|null
     */
    protected $icon = 'fa-pencil';

    /**
     * Set custom fa-icon.
     *
     * @param string $icon
     *
     * @return $this
     */
    public function icon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Render this filed.
     */
    public function render()
    {
        $this->initPlainInput();

        if(isset($this->icon)){
            $this->prepend('<i class="fa '.$this->icon.' fa-fw"></i>');
        }
        $this->defaultAttribute('type', 'text')
        ->defaultAttribute('id', $this->id)
        ->defaultAttribute('name', $this->elementName ?: $this->formatName($this->column))
        ->defaultAttribute('value', $this->getOld())
        ->defaultAttribute('class', 'form-control '.$this->getElementClassString())
        ->defaultAttribute('placeholder', $this->getPlaceholder());

        $this->addVariables([
            'prepend' => $this->prepend,
            'append'  => $this->append,
        ]);

        return parent::render();
    }

    /**
     * Add inputmask to an elements.
     *
     * @param array<mixed> $options
     *
     * @return $this
     */
    public function inputmask($options)
    {
        $options = json_encode_options($options);

        $this->script = "$('{$this->getElementClassSelector()}').inputmask($options);";

        return $this;
    }

    /**
     * Add datalist element to Text input.
     *
     * @param array<mixed> $entries
     *
     * @return $this
     */
    public function datalist($entries = [])
    {
        $this->defaultAttribute('list', "list-{$this->id}");

        $datalist = "<datalist id=\"list-{$this->id}\">";
        foreach ($entries as $k => $v) {
            $datalist .= "<option value=\"{$k}\">{$v}</option>";
        }
        $datalist .= '</datalist>';

        return $this->append($datalist);
    }
}
