# How to Cover PHP Classes with Documentation

## Purpose
This guideline explains how to write effective PHP documentation that is optimized for both human readers and LLM consumption.

## Requirements
- Use PHP 8.4 with constructor property promotion and named arguments
- Include PHPDoc annotations for all classes, methods, and properties
- Maintain consistent formatting with explicit section markers

## PHPDoc Annotation Guidelines

### Class Documentation
```php
/**
 * Short description of class purpose.
 *
 * Longer description with additional context if needed.
 *
 * @link https://example.com External reference documentation
 * @see RelatedClass For related functionality
 * @template T of object
 * @template-implements CollectionInterface<T>
 */
class MyClass implements CollectionInterface
{
}
```

### Method Documentation
```php
/**
 * Short description of what the method does.
 *
 * @param string $parameter Description of parameter
 * @param array<int, string> $complexParameter Description with complex type
 * @return bool Description of return value
 * @throws \Exception When something goes wrong
 * @see self::relatedMethod() For related functionality
 * @see AnotherClass::someMethod() Cross-class reference
 * @template-param class-string<T> $className
 */
public function doSomething(string $parameter, array $complexParameter, string $className): bool
{
}
```

### Property Documentation
```php
/**
 * Description of what this property represents.
 *
 * @var string|null
 */
private ?string $property;

/**
 * Collection of entities.
 *
 * @var array<int, T>
 * @psalm-var non-empty-array<int, T>
 */
private array $entities;
```

### Inline References
Use inline notation within documentation blocks:
```php
/**
 * This method works similarly to {@see ParentClass::parentMethod()}.
 * For configuration options, check {@link https://example.com/docs}.
 */
```

## Advanced Type Annotations

### Extended Types
Use extended type annotations for more precise type information:

```php
/**
 * @param non-empty-string $id Unique identifier that cannot be empty.
 * @param int<1, max> $count Only positive integers (> 0).
 * @param class-string<\Exception> $exceptionClass Class name that extends Exception.
 * @param callable(string): bool $validator Function that takes string and returns bool.
 * @param int<0, max> $someFlag Very long comment that is not fit for a single line may be
 *        written in multiple lines. Make sure to use the same indentation.
 * @return array<non-empty-string, object> Associative array with non-empty string keys.
 */
```

Common extended types:
- `non-empty-string` - String that cannot be empty
- `positive-int` - Integer greater than zero
- `negative-int` - Integer less than zero
- `non-empty-array` - Array with at least one element
- `class-string` - String containing a valid class name
- `class-string<T>` - String containing a class name that extends/implements T
- `callable(ParamType): ReturnType` - Callable with specific signature

### Templates

Use templates for generic classes and methods:

```php
/**
 * @template T of object
 */
class Collection
{
    /**
     * @param T $item
     * @return void
     */
    public function add($item): void {}

    /**
     * @return array<int, T>
     */
    public function getAll(): array {}
}

/**
 * @template T of \DateTimeInterface
 * @extends Collection<T>
 */
class DateCollection extends Collection
{
    /**
     * @template U of T
     * @param class-string<U> $className
     * @return U
     */
    public function createNew(string $className)
    {
        // Implementation
    }
}
```

Template annotations:
- `@template T` - Define a template parameter T
- `@template T of SomeClass` - Define a template parameter T that extends SomeClass
- `@template-extends Parent<T>` - Specify template parameter for parent class
- `@template-implements Interface<T>` - Specify template parameter for implemented interface
- `@template-use Trait<T>` - Specify template parameter for used trait
- `@template-param` - Document template parameter in method signature
- `@template-return` - Document template return type

## Best Practices

- Use clear type definitions including generics for collections (`array<int, string>`)
- Include complete parameter descriptions for all method arguments
- Document exceptions that may be thrown
- Use `@link` for external URLs and resources
- Use `@see` to reference other classes and methods
- Use straightforward, direct language without unnecessary adjectives
- Include meaningful examples for complex functionality
- Use extended types for more precise type information
- Document generic classes and methods with templates

## Avoid In Documentation

- Licensing information (use separate LICENSE files)
- Contribution guidelines (use CONTRIBUTING.md)
- Author information (use Git history)
- Version history (use Git history)
- HTML formatting (use Markdown for formatting)

## Example of Well-Documented Class

```php
<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Manages user authentication operations.
 *
 * This service handles user login, verification, and session management.
 *
 * @see \App\Entity\User The entity this service manages
 * @link https://example.com/auth-docs External authentication documentation
 * @template T of \App\Entity\User
 */
class AuthenticationService
{
    /**
     * User repository for database operations.
     *
     * @var UserRepositoryInterface<T>
     */
    private UserRepositoryInterface $userRepository;

    /**
     * Creates a new authentication service.
     *
     * @param UserRepositoryInterface<T> $userRepository Repository for user data
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Authenticates a user with the provided credentials.
     *
     * This method verifies the username and password combination
     * against the stored user data. It uses the password hashing
     * mechanism defined in {@see \App\Service\PasswordHasher}.
     *
     * @param non-empty-string $username User's unique identifier
     * @param non-empty-string $password User's password (plaintext)
     * @return bool True if authentication succeeds, false otherwise
     * @throws \App\Exception\UserNotFoundException When username doesn't exist
     * @template-return T|null
     */
    public function authenticate(string $username, string $password): bool
    {
        // Implementation
    }

    /**
     * Creates a new instance of a user entity.
     *
     * @param class-string<T> $className
     * @return T
     */
    public function createUserInstance(string $className)
    {
        // Implementation
    }
}
```
