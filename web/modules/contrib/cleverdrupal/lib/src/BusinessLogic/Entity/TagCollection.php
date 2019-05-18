<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 *
 */
class TagCollection implements \Iterator, \Countable, \Serializable {
  /**
   * @var \CleverReach\BusinessLogic\Entity\Tag[]
   */
  private $tags;
  /**
   * @var int
   */
  private $position;

  /**
   * TagCollection constructor. Accepts list of @see \CleverReach\BusinessLogic\Entity\Tag instances to start with.
   *
   * @param \CleverReach\BusinessLogic\Entity\Tag[] $tags
   */
  public function __construct(array $tags = []) {
    $this->tags = $tags;
    $this->position = 0;
  }

  /**
   * Adds tag to collection if it does not exist.
   *
   * @param \CleverReach\BusinessLogic\Entity\Tag $tag
   */
  public function addTag($tag) {
    $this->add(new TagCollection([$tag]));
  }

  /**
   * Adds all tags from another collection. Tags that already exist are not duplicated.
   *
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagCollection
   *   A collection of tags to add to
   *   current collection.
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function add($tagCollection) {
    foreach ($tagCollection as $tag) {
      if (!$this->hasTag($tag)) {
        $this->tags[] = $tag;
      }
    }

    return $this;
  }

  /**
   * Checks whether tag is already in this collection.
   *
   * @param \CleverReach\BusinessLogic\Entity\Tag|string $needle
   *
   * @return bool
   */
  public function hasTag($needle) {
    foreach ($this->tags as $tag) {
      if ($tag->isEqual($needle)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Merges two tag collections and returns new collection. Original collections are not modified.
   *
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagCollection
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function merge($tagCollection) {
    $new = new TagCollection($this->tags);

    return $new->add($tagCollection);
  }

  /**
   * Removes all tags from this collection that exist in given collection.
   *
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagCollection
   *   A collection of tags to be removed.
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function remove($tagCollection) {
    // Udiff will keep array indexes (ids) of original array so we get values to have indexes reset.
    // This is done because if first element is removed, $this->tags[0] will not exist even if there are
    // elements in the array.
    $this->tags = array_values(
        array_udiff(
            $this->tags,
            $tagCollection->toArray(),
            function ($first, $second) {
                /** @var \CleverReach\BusinessLogic\Entity\Tag $first */
                /** @var \CleverReach\BusinessLogic\Entity\Tag $second */
              if ($first->isEqual($second)) {
                return 0;
              }

                return (string) $first < (string) $second ? -1 : 1;
            }
        )
    );

    return $this;
  }

  /**
   * Returns the difference between two collections as a new collection.
   * Resulting collection will give all tags that exist in original collection and do not
   * exist in given $tagCollection. Original collections are not modified.
   *
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $tagCollection
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function diff($tagCollection) {
    $new = new TagCollection($this->tags);

    return $new->remove($tagCollection);
  }

  /**
   * Marks all tags from collection as deleted.
   */
  public function markDeleted() {
    foreach ($this->tags as $tag) {
      $tag->markDeleted();
    }
  }

  /**
   * Gets all tags as an array.
   *
   * @return \CleverReach\BusinessLogic\Entity\Tag[]
   */
  public function toArray() {
    return $this->tags;
  }

  /**
   * Gets all tags as array of string.
   *
   * @return \CleverReach\BusinessLogic\Entity\Tag[]
   */
  public function toStringArray() {
    $result = [];
    foreach ($this->tags as $tag) {
      $result[] = (string) $tag;
    }

    return $result;
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function current() {
    return $this->tags[$this->position];
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function next() {
    $this->position++;
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function key() {
    return $this->position;
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function valid() {
    return isset($this->tags[$this->position]);
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function count() {
    return count($this->tags);
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function serialize() {
    return serialize([$this->position, $this->tags]);
  }

  /**
   * Internal. Do not use directly.
   *
   * @inheritdoc
   */
  public function unserialize($serialized) {
    list($this->position, $this->tags) = unserialize($serialized);
  }

}
