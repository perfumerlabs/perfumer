<?php

namespace Perfumerlabs\Perfumer\ContractAnnotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Perfumerlabs\Perfumer\LocalVariable;
use Perfumerlabs\Perfumer\PerfumerException;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
#[\Attribute(
    \Attribute::TARGET_METHOD |
    \Attribute::TARGET_CLASS |
    \Attribute::IS_REPEATABLE
)]
class Set extends Code
{
    public function __construct(
        public $name = null,
        public $value = null,
        public $tags = [],
        ...$args
    )
    {
        parent::__construct(...$args);
    }

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

        try {
            $this->getMethodData()->addLocalVariable($variable, false);
        } catch (PerfumerException $exception) {
            $message = 'In '.static::class.': '.$exception->getMessage();
            throw new PerfumerException($message);
        }
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
