<?php

namespace ZaberDev\Context\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ZaberDev\Context\Contracts\ContextResolver;

class RequestResolver implements ContextResolver
{
    public function __construct(
        protected Request $request,
        protected ?string $parameterName = null
    ) {}

    /**
     * Attempt to resolve the active context from the request.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolve(): ?Model
    {
        // If a specific parameter name is provided, look for it in the route
        if ($this->parameterName && $this->request->route()) {
            $contextModel = $this->request->route($this->parameterName);
            if ($contextModel instanceof Model) {
                return $contextModel;
            }
        }

        // Alternatively, check if middleware manually set it on request attributes
        if ($this->request->attributes->has('context')) {
            $context = $this->request->attributes->get('context');
            if ($context instanceof Model) {
                return $context;
            }
        }

        return null;
    }
}
