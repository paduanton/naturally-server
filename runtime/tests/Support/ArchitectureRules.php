<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/** Static checks of the approved boundaries; source is parsed, never executed. */
final class ArchitectureRules
{
    public function violations(string $source, string $path): array
    {
        try {
            $nodes = (new ParserFactory())->createForHostVersion()->parse($source) ?? [];
            $nodes = (new NodeTraverser(new NameResolver()))->traverse($nodes);
        } catch (\PhpParser\Error) {
            return ['Invalid PHP source.'];
        }
        $finder = new NodeFinder();
        $violations = [];
        foreach ($finder->findInstanceOf($nodes, Node\Expr\FuncCall::class) as $call) {
            if ($call->name instanceof Node\Name) {
                $name = strtolower($call->name->toString());
                if ($name === 'env' || ($name === 'getenv' && $path !== 'app/Shared/Infrastructure/RuntimeApplicationFactory.php')) {
                    $violations[] = 'Environment reads must remain in the central pointer loader.';
                }
            }
        }
        if (!preg_match('~^app/Modules/([^/]+)/([^/]+)/~', $path, $modulePath)) {
            return array_values(array_unique($violations));
        }
        [, $module, $layer] = $modulePath;
        if (!in_array($module, ['Identity', 'Recipes', 'Community', 'Media'], true) || !in_array($layer, ['Domain', 'Application', 'Infrastructure', 'Http'], true)) {
            $violations[] = 'Module or layer is outside the approved architecture.';
        }
        if (!str_contains($path, '/Database/Migrations/')) {
            $expected = 'App\\'.str_replace('/', '\\', substr(dirname($path), 4));
            $namespaces = $finder->findInstanceOf($nodes, Node\Stmt\Namespace_::class);
            if (count($namespaces) !== 1 || $namespaces[0]->name?->toString() !== $expected) {
                $violations[] = 'Namespace must match the module and layer directory.';
            }
        }
        foreach ($finder->findInstanceOf($nodes, Node\Name::class) as $name) {
            $dependency = $name->toString();
            if ($layer === 'Domain' && (str_starts_with($dependency, 'Illuminate\\') || str_starts_with($dependency, 'Laravel\\') || str_starts_with($dependency, 'Symfony\\'))) {
                $violations[] = 'Domain must not depend on a framework.';
            }
            if (preg_match('~^App\\\\Modules\\\\([^\\\\]+)\\\\([^\\\\]+)(?:\\\\|$)~', $dependency, $target)) {
                [, $targetModule, $targetLayer] = $target;
                if ($targetModule !== $module) {
                    if ($layer === 'Domain' || $targetLayer !== 'Application') {
                        $violations[] = 'Other modules must be accessed through Application contracts.';
                    }
                } elseif (($layer === 'Domain' && $targetLayer !== 'Domain')
                    || ($layer === 'Application' && in_array($targetLayer, ['Infrastructure', 'Http'], true))
                    || ($layer === 'Http' && in_array($targetLayer, ['Domain', 'Infrastructure'], true))) {
                    $violations[] = 'Dependency reverses the approved direction between layers.';
                }
            }
            if ($layer === 'Domain' && str_starts_with($dependency, 'App\\Shared\\')
                && !str_starts_with($dependency, 'App\\Shared\\Domain\\')) {
                $violations[] = 'Domain may only access shared Domain types.';
            }
            if (($layer === 'Domain' || $layer === 'Application')
                && (str_starts_with($dependency, 'App\\Shared\\Infrastructure\\') || str_starts_with($dependency, 'App\\Shared\\Http\\'))) {
                $violations[] = 'Domain/Application must not depend on shared adapters.';
            }
        }
        if ($layer === 'Application') {
            foreach ($finder->findInstanceOf($nodes, Node\Stmt\ClassMethod::class) as $method) {
                if (!$method->isPublic()) {
                    continue;
                }
                $types = array_map(static fn (Node\Param $parameter) => $parameter->type, $method->params);
                $types[] = $method->returnType;
                foreach ($types as $type) {
                    if ($type === null) {
                        continue;
                    }
                    foreach ($finder->findInstanceOf($type, Node\Name::class) as $name) {
                        if (str_starts_with($name->toString(), 'Illuminate\\Database\\Eloquent\\')
                            || in_array($name->toString(), [
                                'Illuminate\\Http\\Request', 'Illuminate\\Http\\Response', 'Illuminate\\Http\\JsonResponse',
                                'Symfony\\Component\\HttpFoundation\\Request', 'Symfony\\Component\\HttpFoundation\\Response',
                                'Symfony\\Component\\HttpFoundation\\JsonResponse',
                            ], true)) {
                            $violations[] = 'Application public contracts must not expose HTTP or Eloquent types.';
                        }
                    }
                }
            }
        }
        return array_values(array_unique($violations));
    }
}
