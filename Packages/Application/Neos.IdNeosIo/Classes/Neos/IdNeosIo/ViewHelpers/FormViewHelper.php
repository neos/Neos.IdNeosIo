<?php
namespace Neos\IdNeosIo\ViewHelpers;

use Neos\FluidAdaptor\ViewHelpers\FormViewHelper as OriginalFormViewHelper;

/**
 * A custom Form ViewHelper that prevents generation of a CSRF token
 */
class FormViewHelper extends OriginalFormViewHelper
{
    protected function renderCsrfTokenField()
    {
        return '';
    }

}
