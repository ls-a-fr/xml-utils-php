<?php

declare(strict_types=1);

namespace LsaStandard\Sniffs\Commenting;

use Exception;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FunctionCommentSniff as PEARFunctionCommentSniff;
use ReflectionNamedType;

class FunctionCommentSniff extends PEARFunctionCommentSniff
{
    /**
     * Gets the fully qualified name of a class
     *
     * @param  \PHP_CodeSniffer\Files\File  $phpcsFile  The PHPCS file
     * @param  int  $classPtr  The current class pointer
     */
    private function getFullyQualifiedClassName(\PHP_CodeSniffer\Files\File $phpcsFile, int $classPtr): ?string
    {
        $tokens = $phpcsFile->getTokens();

        // Get current class name
        try {
            $className = $phpcsFile->getDeclarationName($classPtr);
        } catch (\Exception) {
            $className = null;
        }

        if (! $className) {
            return null;
        }

        foreach ($tokens as $index => $token) {
            // Namespace declarations lie before class declaration
            if ($index >= $classPtr) {
                break;
            }
            // Found namespace
            if ($token['code'] === T_NAME_QUALIFIED) {
                return $token['content'].'\\'.$className;
            }
        }

        // Class without namespace
        return $className;
    }

    /**
     * Traverse the class hierarchy to find a PHPDoc on function higher, if not declared on the current class
     *
     * @return bool True if the function is documented in the hierarchy, false otherwise
     */
    protected function isOverriddenAndDocumented(\PHP_CodeSniffer\Files\File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        // Find class/trait/interface declaration
        $classPtr = $phpcsFile->findPrevious([T_CLASS, T_TRAIT, T_INTERFACE], $stackPtr);
        if ($classPtr === false) {
            return false;
        }

        $tokenType = $tokens[$classPtr]['code'];
        $className = $this->getFullyQualifiedClassName($phpcsFile, $classPtr);

        $functionName = $phpcsFile->getDeclarationName($stackPtr);
        if (! $functionName) {
            return false;
        }

        switch ($tokenType) {
            case T_CLASS:
            case T_INTERFACE:
                try {
                    $classRef = new \ReflectionClass($className);
                    if (! $classRef) {
                        return false;
                    }
                    $classMethod = $classRef->getMethod($functionName);
                    $docComment = $classMethod->getDocComment();
                    if ($docComment !== false && trim($docComment) !== '') {
                        // DocComment is defined on this method
                        return true;
                    }
                    // Get all ancestors (+ interfaces)
                    $ancestors = array_merge(
                        $classRef->getInterfaces(),
                        $this->getParentClasses($classRef)
                    );

                    foreach ($ancestors as $ancestor) {
                        if (! $ancestor->hasMethod($functionName)) {
                            continue;
                        }

                        $method = $ancestor->getMethod($functionName);

                        // Get PHPDoc comment
                        $docComment = $method->getDocComment();
                        if ($docComment !== false && trim($docComment) !== '') {
                            return true;
                        }
                    }
                } catch (Exception $e) {
                    return false;
                }
                break;
            case T_TRAIT:
                $tokens = $phpcsFile->getTokens();

                $commentEnd = $phpcsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPtr);
                if ($commentEnd === false) {
                    return false;
                }

                // Trouver le début du docblock
                $commentStart = $tokens[$commentEnd]['comment_opener'] ?? null;
                if ($commentStart === null) {
                    return false;
                }
                $comment = '';
                for ($i = $commentStart; $i <= $commentEnd; $i++) {
                    $comment .= str_replace(["\r", "\n", '*', ' '], '', $tokens[$i]['content']);
                }

                return $comment !== '';
                break;
            default: return false;

        }

        return false;
    }

    /**
     * Recursively get all parent classes from a class
     *
     * @param  ReflectionClass  $class  The class to get parents from
     * @return list<ReflectionClass> Class hierarchy
     */
    private function getParentClasses(\ReflectionClass $class): array
    {
        $parents = [];
        while ($parent = $class->getParentClass()) {
            $parents[] = $parent;
            $class = $parent;
        }

        return $parents;
    }

    /**
     * @return array{\ReflectionClass, string> Data
     */
    private function getReflectedClass(\PHP_CodeSniffer\Files\File $phpcsFile, int $stackPtr): array
    {
        try {
            // Get current function name
            $functionName = $phpcsFile->getDeclarationName($stackPtr);

            $classPtr = null;
            foreach ([\T_CLASS, \T_INTERFACE] as $type) {
                $classPtr = $phpcsFile->getCondition($stackPtr, $type);
                if ($classPtr !== null && $classPtr !== false) {
                    break;
                }
            }
            if ($classPtr === null || $classPtr === false) {
                throw new \Exception('Class not found');
            }

            // Get current class name
            $className = $this->getFullyQualifiedClassName($phpcsFile, $classPtr);
            if (! $className || (! class_exists($className) && ! \interface_exists($className))) {
                throw new \Exception('Class is not a valid class name');
            }

            return [new \ReflectionClass($className), $functionName];
        } catch (\Exception $e) {
            return [null, null];
        }
    }

    private function isConstructWithNoParameter(\ReflectionClass $class, string $functionName): bool
    {
        if ($functionName !== '__construct') {
            return false;
        }
        if ($class->getMethod($functionName)->getNumberOfParameters() > 0) {
            return false;
        }

        return true;
    }

    private function isVoidOrStaticOrNeverReturnTypeWithoutReturnTag(\ReflectionClass $class, string $functionName): bool
    {
        try {
            $method = $class->getMethod($functionName);
            $type = $method->getReturnType();
            $docComment = $method->getDocComment();

            if ($type === null || ($type instanceof ReflectionNamedType) === false) {
                return false;
            }

            if ($type->getName() !== 'void' && $type->getName() !== 'static' && $type->getName() !== 'never') {
                return false;
            }

            if ($docComment === false) {
                return true;
            }
            $docComment = str_replace(['\\n', '\\r'], ' ', $docComment);

            if (! in_array('@return', explode(' ', $docComment))) {
                return true;
            }

            return false;
        } catch (\ReflectionException) {
            return false;
        }
    }

    private function isParameterCommentOnNextLine(\PHP_CodeSniffer\Files\File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        $commentEnd = $phpcsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPtr);
        if ($commentEnd === false) {
            return false;
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'] ?? null;
        if ($commentStart === null) {
            return false;
        }

        for ($i = $commentStart; $i <= $commentEnd; $i++) {
            $content = $tokens[$i]['content'];

            if (preg_match('/@param\b/', $content)) {
                $j = $i + 1;
                while (isset($tokens[$j]) && strpos($tokens[$j]['content'], "\n") === false) {
                    $j++; // Check next line
                }
                if (! isset($tokens[$j])) {
                    return false; // No next line
                }
                $j++;
                if (! preg_match('/^\s+$/', $tokens[$j]['content'])) {
                    return false; // No indent. Weird. Return false.
                }
                $j++;
                if ($tokens[$j]['content'] !== '*') {
                    return false; // No star, so no direct comment. Return false.
                }
                $j++;
                if (! preg_match('/^\s{2,}$/', $tokens[$j]['content'])) {
                    return false; // No indent, so no comment on next line. Return false.
                }
                $j++;
                $commentToken = $tokens[$j]['content'];

                if (\strlen($commentToken) > 0) {
                    return true;
                }

                // Empty line or bad indent, return false.
                return false;
            }
        }

        return false;
    }

    public function process(\PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr)
    {
        if ($this->isParameterCommentOnNextLine($phpcsFile, $stackPtr)) {
            return;
        }
        if ($this->isOverriddenAndDocumented($phpcsFile, $stackPtr)) {
            return;
        }
        [$reflectedClass, $functionName] = $this->getReflectedClass($phpcsFile, $stackPtr);
        if ($reflectedClass && $functionName) {

            if ($this->isConstructWithNoParameter($reflectedClass, $functionName)) {
                return;
            }

            if ($this->isVoidOrStaticOrNeverReturnTypeWithoutReturnTag($reflectedClass, $functionName)) {
                return;
            }
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
