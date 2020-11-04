<?php
declare(strict_types=1);

namespace Lcobucci\JWT;

use Closure;
use Lcobucci\JWT\Encoding\DefaultClaimsFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\None;
use Lcobucci\JWT\Validation\Constraint;

/**
 * Configuration container for the JWT Builder and Parser
 *
 * Serves like a small DI container to simplify the creation and usage
 * of the objects.
 */
final class Configuration
{
    private Parser $parser;
    private Signer $signer;
    private Key $signingKey;
    private Key $verificationKey;
    private Validator $validator;

    /** @var Closure(ClaimsFormatter $claimFormatter): Builder */
    private Closure $builderFactory;

    /** @var Constraint[] */
    private array $validationConstraints = [];

    private function __construct(
        Signer $signer,
        Key $signingKey,
        Key $verificationKey,
        ?Encoder $encoder = null,
        ?Decoder $decoder = null
    ) {
        $this->signer          = $signer;
        $this->signingKey      = $signingKey;
        $this->verificationKey = $verificationKey;
        $this->parser          = new Token\Parser($decoder ?? new JoseEncoder());
        $this->validator       = new Validation\Validator();

        $this->builderFactory = static function (ClaimsFormatter $claimFormatter) use ($encoder): Builder {
            return new Token\Builder($encoder ?? new JoseEncoder(), $claimFormatter);
        };
    }

    public static function forAsymmetricSigner(
        Signer $signer,
        Key $signingKey,
        Key $verificationKey,
        ?Encoder $encoder = null,
        ?Decoder $decoder = null
    ): self {
        return new self(
            $signer,
            $signingKey,
            $verificationKey,
            $encoder,
            $decoder
        );
    }

    public static function forSymmetricSigner(
        Signer $signer,
        Key $key,
        ?Encoder $encoder = null,
        ?Decoder $decoder = null
    ): self {
        return new self(
            $signer,
            $key,
            $key,
            $encoder,
            $decoder
        );
    }

    public static function forUnsecuredSigner(
        ?Encoder $encoder = null,
        ?Decoder $decoder = null
    ): self {
        $key = new Key('');

        return new self(
            new None(),
            $key,
            $key,
            $encoder,
            $decoder
        );
    }

    /** @param callable(ClaimsFormatter): Builder $builderFactory */
    public function setBuilderFactory(callable $builderFactory): void
    {
        $this->builderFactory = Closure::fromCallable($builderFactory);
    }

    public function createBuilder(?ClaimsFormatter $claimFormatter = null): Builder
    {
        return ($this->builderFactory)($claimFormatter ?? new DefaultClaimsFormatter());
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function setParser(Parser $parser): void
    {
        $this->parser = $parser;
    }

    public function getSigner(): Signer
    {
        return $this->signer;
    }

    public function getSigningKey(): Key
    {
        return $this->signingKey;
    }

    public function getVerificationKey(): Key
    {
        return $this->verificationKey;
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    /** @return Constraint[] */
    public function getValidationConstraints(): array
    {
        return $this->validationConstraints;
    }

    public function setValidationConstraints(Constraint ...$validationConstraints): void
    {
        $this->validationConstraints = $validationConstraints;
    }
}
