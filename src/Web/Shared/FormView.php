<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Yiisoft\FormModel\FormModelInterface;
use Yiisoft\Html\Html;

/**
 * Pequeno helper de template para formulários baseados em FormModel:
 * nomes de campos (`PostForm[title]`), valores e mensagens de erro.
 */
final readonly class FormView
{
    public function __construct(
        private FormModelInterface $form,
    ) {}

    public function name(string $property): string
    {
        return $this->form->getFormName() . '[' . $property . ']';
    }

    public function value(string $property): string
    {
        $value = $this->form->getPropertyValue($property);
        return Html::encode(is_bool($value) ? (int) $value : (string) $value);
    }

    public function error(string $property): ?string
    {
        if (!$this->form->isValidated()) {
            return null;
        }
        return $this->form->getValidationResult()->getPropertyErrorMessages($property)[0] ?? null;
    }

    public function fieldClass(string $property, string $base = 'field'): string
    {
        return $this->error($property) !== null ? $base . ' has-error' : $base;
    }

    public function errorTag(string $property): string
    {
        $error = $this->error($property);
        return $error === null ? '' : '<div class="field__error">' . Html::encode($error) . '</div>';
    }

    /**
     * Erros que não pertencem a um campo específico.
     *
     * @return string[]
     */
    public function commonErrors(): array
    {
        return $this->form->isValidated() ? $this->form->getValidationResult()->getCommonErrorMessages() : [];
    }
}
