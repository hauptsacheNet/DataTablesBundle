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
    public function setSortings($sortings)
    {
        $this->sortings = $sortings;
    }

    /**
     * @return array
     */
    public function getSortings()
    {
        return $this->sortings;
    }

    /**
     * @param number $index
     * @return array
     */
    public function getSorting($index)
    {
        return array_key_exists($index, $this->sortings)
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

}