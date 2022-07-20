<?php

namespace Xttribute\Xttribute;

use Attribute;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Xttribute\Xttribute\Exceptions\IdentifyValueException;

/**
 * @template T
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Caster implements Xttribute
{
    /**
     * @param string $xpath
     * @param T $castTo
     */
    public function __construct(
        private readonly string $xpath,
        private readonly string $castTo
    ) {}

    /**
     * @param DOMDocument $doc
     * @return T
     * @throws ReflectionException
     * @throws IdentifyValueException
     */
    public function value(DOMDocument $doc): mixed
    {
        $values = [];
        $ref = new ReflectionClass($this->castTo);
        $nodeList = (new DOMXPath($doc))->query($this->xpath);

        $node = $this->requireSingleDOMNode($nodeList);

        $scopedDoc = new DomDocument();
        $scopedDoc->appendChild($scopedDoc->importNode($node, true));

        foreach ($ref->getProperties() as $prop) {
            /** @var Xttribute $attr */
            foreach ($prop->getAttributes(Xttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attrRef) {
                $attr = $attrRef->newInstance();
                $values[] = $attr->value($scopedDoc);
            }
        }

        return $ref->newInstance(...$values);
    }

    /**
     * DOMNodeList must contain a single DOMNode which has no children,
     * except a value
     */
    private function requireSingleDOMNode(DOMNodeList $list): DOMNode
    {
        if ($list->count() !== 1) {
            throw new IdentifyValueException();
        }

        return $list->item(0);
    }
}
