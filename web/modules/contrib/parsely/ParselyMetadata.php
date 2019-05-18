<?php
namespace Drupal\parsely;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Psr\Log\InvalidArgumentException;

class ParselyMetadata {

    protected   $creator;
    protected   $keywords;
    protected   $articleId;
    protected   $articleSection;
    protected   $context;
    protected   $dateCreated;
    protected   $headline;
    protected   $type;
    protected   $url;

    public function __construct($node) {
        $this->articleID = $this->setID($node);
        $this->creator = $this->setCreator($node);
        $this->datePublished = $this->setDate($node);
        $this->keywords = $this->setTags($node);
        $this->articleSection = $this->setSection($node);
        $this->schemaType = $this->setSchemaType($node);
        $this->headline = $this->setHeadline($node);
		$this->thumbnailUrl = $this->setImageURL($node);


    }

    /* ~~~ Setters (protected) ~~~ */



    /**
     * @param $node Node
     * @return string
     */

    protected function setID($node) {

        $prefix = \Drupal::config('parsely.settings')->get('parsely_content_id_prefix');
        if (!empty($prefix)) {
            $prefix = $prefix . '-';
        }

        $node_id = $node->id();
        return $prefix.$node_id;

    }

	/**
	 * @param $node Node
	 * @return string
	 */
	protected function setImageURL($node) {
		if (!$node->hasField('field_image')) {
			return "";
		}
			$file_path = $node->get('field_image')->entity->uri->value;
		if ($file_path) {
			$wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($file_path);
			return $wrapper->getExternalUrl();
		}
		return "";
	}


    /**
     * @param $node Node
     * @return string
     */
    protected function setSchemaType($node) {
        $schema_type = 'WebPage';
        $nodeTypes = \Drupal::config('parsely.settings')->get('parsely_nodes_wrap')['parsely_nodes'];
        $current_node_type = $node->getType();
        foreach ($nodeTypes as $key => $value) {
            if ($current_node_type == $key && $value) {
                $schema_type = 'NewsArticle';
                }
        }
        return $schema_type;
    }



    // @TODO: profile this function.

    /**
     * @param $node Node
     * @return string
     */

    protected function setCreator($node) {
                $author = $node->getOwner();
                return $author->getDisplayName();
    }

	/**
	 * @param $node Node
	 * @return string
	 */
	protected function setHeadline($node) {
		return $node->getTitle();
	}


    /**
     * @param $node Node
     * @return false|string
     */
    protected function setDate($node) {

        $pub_date = $node->getCreatedTime();

        return gmdate("Y-m-d\TH:i:s\Z", $pub_date);
    }


    /**
     * @param $node Node
     * @return array
     */
    protected function setTags($node) {

        $tags = [];
        $vocabularies = \Drupal::config('parsely.settings')->get('parsely_tag_vocabularies');
        if (!\Drupal::moduleHandler()->moduleExists('taxonomy') || $vocabularies === NULL || $vocabularies === '') {
            return array();
        }
        foreach($vocabularies as $vocab => $value) {
        	if ($value === 0) {
				continue;
			}
            $entity = Vocabulary::load($vocab);
            $clean_term_name = $entity->get('vid');
            try {
                $term_ids = $node->get('field_'.$clean_term_name)->getValue();
            }
            catch (\InvalidArgumentException $e) {
                $term_ids = NULL;
            }


            if ($term_ids) {
                foreach ($term_ids as $term_id) {
                    $term_name = Term::load($term_id['target_id'])->getName();

                    array_push($tags, $term_name);
                }
            }

        }
        return $tags;
    }

    protected function setSection($node) {

        $section = parsely_get_section($node);
        // not sure how this ever ends up null, but it does sometimes
    	return $section;


    }


    /* ~~~ Getters (public) ~~~ */

    public function getCreator() {

        return $this->creator;

    }

    public function getDate() {

        return $this->datePublished;

    }

    public function getID() {

        return $this->articleID;

    }

    public function getSection() {

        return $this->articleSection;

    }

    public function getTags() {

        return $this->keywords;
    }

    public function getSchemaType() {
        return $this->schemaType;
    }

	public function getHeadline() {
		return $this->headline;
	}

	public function getThumbnail() {
		return $this->thumbnailUrl;
	}

    /**
     * @param $node Node
     * @return string
     */
    public function getURL($node) {
        $result = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
        return $result;
    }

}
