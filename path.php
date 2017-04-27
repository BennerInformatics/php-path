<?php

if (!function_exists('path_normalize_reduce')) {
    /**
     * Helper callback to array_reduce. This is not designed to be called
     * by itself; it is used inside path_join
     *
     * @param array $carry The carry from the array_reduce call
     * @param string $item The current item
     * @return array Array after running the function on it
     */
    function path_normalize_reduce(array $carry, string $item): array {
        if ($item === '.') { // Same directory
            return $carry;
        } else if ($item !== '..' || !count($carry)) {
            // This adds the element to the end of the carry array in two cases
            // 1: Normal part of the path
            // 2: '..', which means go back a directory. Normally we'd remove the last
            //    element of the array in this case, but if the array is empty it means
            //    that all prior elements have also been removed, and the resulting path
            //    will actually go back directories in the end. Therefore, we add it to
            //    the array instead
            $carry[] = $item;
            return $carry;
        }

        $last = array_pop($carry);

        if ($last === '..') {
            // if the previous element is also a .., that means we hit case 2 in the above conditional,
            // so we need to re-add the array element
            array_push($carry, $last, $item);
        }

        return $carry;
    }
}

if (!function_exists('path_join')) {
    function path_join(string $first, string ...$paths): string {
        if (empty($first)) {
            throw new \InvalidArgumentException('The first argument to path_join cannot be an empty string.');
        }

        $splitRegex = '/\/|\\\\/';
        $letter = null;
        $parts = [];

        $firstParts = preg_split($splitRegex, rtrim($first, '/\\'));

        // Check if the first part of the path is a drive letter
        if ($firstParts && count($firstParts) > 0 && preg_match('/[A-Za-z]:/', $firstParts[0])) {
            $letter = array_shift($firstParts);
        }

        $parts = array_reduce($firstParts, 'path_normalize_reduce', []);

        foreach ($paths as $path) {
            // Split path into its composite parts
            $pathParts = preg_split($splitRegex, trim($path, '/\\'));
            // Run it through the reduce function
            $parts = array_reduce($pathParts, 'path_normalize_reduce', $parts);
        }

        if ($letter && !empty($parts) && $parts[0] === '..') {
            // Letter exists and path ended up going back from that
            // This is an invalid path on Windows, so we should throw an exception
            throw new \InvalidArgumentException('Invalid folder path (you can\'t go back past the root!)');
        }

        // Join the parts with a '/'
        $joinedPath = implode('/', $parts);
        return $letter ? "{$letter}/{$joinedPath}" : $joinedPath;
    }
}

if (!function_exists('path_is_absolute')) {
    /**
     * Checks if a path is absolute
     * @param string $path Path to check
     * @return bool
     */
    function path_is_absolute(string $path): bool {
        return !empty($path) && (bool) preg_match('/^(\/|\\\\|[A-Za-z]:(?![^\/\\\\]))/', $path);
    }
}

if (!function_exists('path_resolve')) {
    /**
     * Similar to path_join except it will always return an absolute path.
     * If the first argument to this function is *not* an absolute path, this function
     * will prepend the current working directory to it.
     *
     * PHP does have a realpath method, however realpath does two things that we don't want:
     *   1) It caches the path found
     *   2) It returns null if the file/folder doesn't exist
     *
     * @param string ...$paths paths to join together & resolve to an absolute path
     */
    function path_resolve(string $first, string ...$paths): string {
        return path_is_absolute($first) ? path_join($first, ...$paths) : path_join(getcwd(), $first, ...$paths);
    }
}
