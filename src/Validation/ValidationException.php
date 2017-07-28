<?php

namespace Nova\Validation;

use Nova\Support\Contracts\MessageProviderInterface;

use RuntimeException;


class ValidationException extends RuntimeException
{
    /**
     * The message provider implementation.
     *
     * @var \Nova\Support\Contracts\MessageProviderInterface
     */
    protected $provider;

    /**
     * Create a new validation exception instance.
     *
     * @param  \Nova\Support\MessageProvider  $provider
     * @return void
     */
    public function __construct(MessageProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get the validation error message provider.
     *
     * @return \Nova\Support\MessageBag
     */
    public function errors()
    {
        return $this->provider->getMessageBag();
    }

    /**
     * Get the validation error message provider.
     *
     * @return \Nova\Support\MessageProviderInterface
     */
    public function getMessageProvider()
    {
        return $this->provider;
    }
}
