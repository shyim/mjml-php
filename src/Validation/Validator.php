<?php

declare(strict_types=1);

namespace MjmlPHP\Validation;

use MjmlPHP\Component\ComponentRegistry;
use MjmlPHP\Parser\Node;
use MjmlPHP\Validation\Rule\ValidAttributesRule;
use MjmlPHP\Validation\Rule\ValidChildrenRule;
use MjmlPHP\Validation\Rule\ValidTagRule;
use MjmlPHP\Validation\Rule\ValidationRuleInterface;
use MjmlPHP\Validation\Rule\ValidTypesRule;

final class Validator
{
    private const SKIP_ELEMENTS = ['mjml'];

    /** @var list<ValidationRuleInterface> */
    private readonly array $rules;

    /**
     * @param list<ValidationRuleInterface>|null $rules Custom rules, or null for defaults
     */
    public function __construct(
        private readonly ComponentRegistry $registry,
        ?array $rules = null,
    ) {
        $this->rules = $rules ?? [
            new ValidTagRule(),
            new ValidAttributesRule(),
            new ValidChildrenRule(),
            new ValidTypesRule(),
        ];
    }

    /**
     * Validate a parsed MJML node tree.
     *
     * @return list<ValidationError>
     */
    public function validate(Node $root): array
    {
        return $this->validateNode($root);
    }

    /**
     * @return list<ValidationError>
     */
    private function validateNode(Node $node): array
    {
        $errors = [];

        if (!\in_array($node->tagName, self::SKIP_ELEMENTS, true)) {
            foreach ($this->rules as $rule) {
                $result = $rule->validate($node, $this->registry);

                if ($result instanceof ValidationError) {
                    $errors[] = $result;
                } elseif (\is_array($result)) {
                    array_push($errors, ...$result);
                }
            }
        }

        foreach ($node->children as $child) {
            array_push($errors, ...$this->validateNode($child));
        }

        return $errors;
    }
}
