<?php

namespace CommerceGuys\Tax\Resolver;

trait ResolverSorterTrait
{
    /**
     * Sorts the given resolvers.
     *
     * @param array $resolvers An array of resolvers.
     *
     * @return array An array of resolvers sorted by priority.
     */
    protected function sortResolvers(array $resolvers)
    {
        usort($resolvers, function ($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return ($a['priority'] > $b['priority']) ? -1 : 1;
        });

        $sortedResolvers = [];
        foreach ($resolvers as $resolver) {
            $sortedResolvers[] = $resolver['resolver'];
        }

        return $sortedResolvers;
    }
}
