<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 04.06.14
 * Time: 17:25
 */

namespace Hn\DataTablesBundle\Model;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;

class DataTableColumn
{
    /**
     * @var string
     */
    private $propertyPath;

    /**
     * @var array
     */
    private $sortings;

    /**
     * @var DataTransformerInterface
     */
    private $dataTransformers = array();

    /**
     * @var string|\Closure
     */
    private $template;

    /**
     * @var bool
     */
    private $virtual;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|\Closure
     */
    private $link;

    /**
     * @var string|\Closure
     */
    private $fixedValue;

    /**
     * @var string
     */
    private $className;

    public function __construct($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * Returns a name with no special characters.
     * This is normally generated out of the property path
     *
     * @return string
     */
    public function getName()
    {
        return preg_replace('/\W/', '_', $this->getPropertyPath());
    }

    /**
     * @param array $sortings
     */
    public function setSortings(array $sortings = null)
    {
        $this->sortings = $sortings;
    }

    /**
     * @return array
     */
    public function getSortings()
    {
        return is_array($this->sortings) ? $this->sortings : array();
    }

    /**
     * @param number $index
     * @return array
     */
    public function getSorting($index)
    {
        return array_key_exists($index, $this->getSortings())
            ? $this->sortings[$index] : array();
    }

    /**
     * @return bool
     */
    public function hasSortings()
    {
        return !empty($this->sortings);
    }

    /**
     * @param DataTransformerInterface $transformer
     */
    public function appendDataTransformer(DataTransformerInterface $transformer)
    {
        $this->dataTransformers[] = $transformer;
    }

    /**
     * @param DataTransformerInterface $transformer
     */
    public function prependDataTransformer(DataTransformerInterface $transformer)
    {
        array_unshift($this->dataTransformers, $transformer);
    }

    /**
     * @return DataTransformerInterface
     */
    public function getDataTransformer()
    {
        return new DataTransformerChain($this->dataTransformers);
    }

    /**
     * @param string|\Closure $template
     */
    public function setTemplate($template)
    {
        if (!is_string($template) && !is_callable($template)) {
            $type = is_object($template) ? get_class($template) : gettype($template);
            throw new \RuntimeException("Template must be a string or a callable, got $type");
        }

        $this->template = $template;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function getTemplate($value)
    {
        if ($this->template === null) {
            $type = is_object($value) ? get_class($value) : gettype($value);
            switch ($type) {
                case 'string':
                    return 'HnDataTablesBundle:column:string.html.twig';

                case 'integer':
                case 'double':
                case 'float':
                    return 'HnDataTablesBundle:column:number.html.twig';

                case 'boolean':
                    return 'HnDataTablesBundle:column:boolean.html.twig';

                case 'NULL':
                    return 'HnDataTablesBundle:column:null.html.twig';

                case 'DateTime':
                    return 'HnDataTablesBundle:column:dateTime.html.twig';

                default:
                    // there is no known template so just try plain echo
                    return 'HnDataTablesBundle:column:plain.html.twig';
            }
        }

        if (is_callable($this->template)) {
            $template = $this->template;
            $template = $template($value);

            if (!is_string($template)) {
                $type = is_object($template) ? get_class($template) : gettype($template);
                throw new \RuntimeException("Template callback must return a string, got $type");
            }

            return $template;
        }

        return $this->template;
    }

    /**
     * @return boolean
     */
    public function isVirtual()
    {
        return $this->fixedValue || $this->virtual;
    }

    /**
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        if (!$virtual && $this->fixedValue) {
            throw new \RuntimeException("Columns with a fixed value are always virtual.");
        }

        $this->virtual = (bool)$virtual;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param null|string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return \Closure|string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param \Closure|string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return \Closure|string
     */
    public function getFixedValue()
    {
        return $this->fixedValue;
    }

    /**
     * @param \Closure|string $fixedValue
     */
    public function setFixedValue($fixedValue)
    {
        $this->fixedValue = $fixedValue;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }
}