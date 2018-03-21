<?php

namespace Nova\Cache;


abstract class TaggableStore
{

    /**
     * Begin executing a new tags operation.
     *
     * @param  string  $name
     * @return \Nova\Cache\TaggedCache
     */
    public function section($name)
    {
        return $this->tags($name);
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Nova\Cache\TaggedCache
     */
    public function tags($names)
    {
        $names = is_array($names) ? $names : func_get_args();

        return new TaggedCache($this, new TagSet($this, $names));
    }
}
