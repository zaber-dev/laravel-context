<?php

namespace ZaberDev\Context;

use Illuminate\Support\Manager;
use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\Contracts\ContextResolver;
use ZaberDev\Context\Drivers\MemoryContextDriver;

class ContextManager extends Manager
{
    /**
     * Registered context resolvers.
     *
     * @var array<int, ContextResolver|callable>
     */
    protected array $resolvers = [];

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('context.driver', 'memory');
    }

    /**
     * Create an instance of the Memory driver.
     *
     * @return \ZaberDev\Context\Contracts\ContextDriver
     */
    public function createMemoryDriver()
    {
        return new MemoryContextDriver($this, 'memory');
    }

    /**
     * Register a context resolver.
     */
    public function resolver(ContextResolver|callable $resolver): void
    {
        $this->resolvers[] = $resolver;
    }
    
    /**
     * Resolve the active context from registered resolvers.
     */
    public function resolve(): ?Model
    {
        foreach ($this->resolvers as $resolver) {
            $context = is_callable($resolver) ? $resolver() : $resolver->resolve();
            if ($context instanceof Model) {
                return $context;
            }
        }
        
        return null;
    }
    
    /**
     * Fire a context event via the application's event dispatcher.
     */
    public function fireEvent(string $name, array $payload = []): void
    {
        $eventClass = "\\ZaberDev\\Context\\Events\\" . $name;
        
        if (class_exists($eventClass) && $this->container->bound('events')) {
            $this->container->make('events')->dispatch(new $eventClass(...array_values($payload)));
        }
    }
}
