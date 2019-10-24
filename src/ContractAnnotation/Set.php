<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Perfumerlabs\Perfumer\LocalVariable;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Set extends Code
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var int
     */
    protected $_local_variable_view = LocalVariable::VIEW_REGULAR;

    /**
     * @var string
     */
    private $_id;

    public function onCreate(): void
    {
        $this->_is_validatable = false;

        parent::onCreate();
    }

    public function onAnalyze(): void
    {
        parent::onAnalyze();

        $variable = new LocalVariable();
        $variable->name = $this->name;
        $variable->view = $this->_local_variable_view;
        $variable->init = false;

        $this->getMethodData()->addLocalVariable($variable, false);
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $code = '$' . $this->name . ' = $' . $this->value . ';';

        $this->_code = $code;

        $id = '_set__' . $this->name . '__' . $this->value;

        $this->setId($id);
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setId(string $id): void
    {
        $this->_id = $id;
    }
}
