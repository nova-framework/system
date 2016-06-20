<?php

namespace Nova\Database\ORM\Relations;

use Nova\Database\ORM\Builder;
use Nova\Database\ORM\Relations\Pivot;


class MorphPivot extends Pivot
{
    /**
     * The type of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected $morphType;

    /**
     * Set the keys for a save update query.
     *
     * @param  \Nova\Database\ORM\Builder  $query
     * @return \Nova\Database\ORM\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->morphType, $this->getAttribute($this->morphType));

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Delete the pivot model record from the database.
     *
     * @return int
     */
    public function delete()
    {
        $query = $this->getDeleteQuery();

        $query->where($this->morphType, $this->getAttribute($this->morphType));

        return $query->delete();
    }

    /**
     * Set the morph type for the pivot.
     *
     * @param  string  $morphType
     * @return \Nova\Database\ORM\Relations\MorphPivot
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

}
