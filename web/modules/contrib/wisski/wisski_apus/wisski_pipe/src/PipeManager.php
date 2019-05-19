<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\PipeManager.
 */

namespace Drupal\wisski_pipe;
use Psr\Log\LoggerInterface;


/**
 * Processor service to handle pipe execution.
 */
class PipeManager {
  
  
  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entity_manager;

  
  /**
   * @var array
   */
  protected $hierarchy;


  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   */
  protected function getEntityManager() {
    if (!isset($this->entity_manager)) {
      $this->entity_manager = \Drupal::entityManager();
    }
    return $this->entity_manager;
  }


  /**
   * Runs a pipe.
   *
   * @see PipeInterface::run()
   * 
   * @param pipe_id
   *  The ID of the pipe to be run. 
   * @param data
   *  The data to be processed. Note that this parameter may be of any type.
   * @param logger
   *  Optionally provide a logger that the pipe/processors can log to.
   *
   * @return object
   *  The processed data object
   */
  public function run($pipe_id, $data, $ticket = '', LoggerInterface $logger = NULL) {
    
    $pipe = $this->load($pipe_id);
    // if the pipe does not exist, $pipe is NULL, so we provoke
    // a NullPointerException here.
    if (empty($pipe)) {
      throw new \RuntimeException("Cannot run pipe. No such pipe ID: \"$pipe_id\"");
    }
    $data = $pipe->run($data, $ticket, $logger);
    return $data;

  }

  
  /**
   * Loads and returns the pipes with the given IDs or all pipes.
   *
   * @see EntityManager::loadMultiple()
   *
   * @param ids
   *  The IDs of the pipe or NULL
   *
   * @return array of Pipes keyed by their IDs
   */
  public function loadMultiple(array $ids = NULL) {
    return $this->getEntityManager()->getStorage('wisski_pipe')->loadMultiple($ids);
  }
  

  /**
   * Loads all pipes that match all of the given flags.
   *
   * @param tags an array of tags or a string with a single tag
   *
   * @return array of Pipes keyed by their IDs
   */
  public function loadByTags($tags = array()) {
    $tags = (array) $tags;  // make string an array
    $pipes = $this->getEntityManager()->getStorage('wisski_pipe')->loadMultiple();
    $cnt = count($tags);
    if ($cnt) {
      foreach ($pipes as $pid => $pipe) {
        $sect = array_intersect($tags, $pipe->getTags());
        if (count($sect) != $cnt) {
          unset($pipes[$pid]);
        }
      }
    }
    return $pipes;
  }


  /**
   * Loads and returns the pipe with the given ID.
   *
   * @see EntityManager::load()
   *
   * @param id
   *  The ID of the pipe
   *
   * @return Pipe
   */
  public function load($id) {
    return $this->getEntityManager()->getStorage('wisski_pipe')->load($id);
  }
  
  

  public function buildPipeHierarchy($reset = FALSE) {
    
    if ($reset || !isset($this->hierarchy)) {

      $children = [];
      $parents = [];
      foreach ($this->loadMultiple() as $pipe) {
        if (!isset($children[$pipe->id()])) $children[$pipe->id()] = [];
        foreach ($pipe->getProcessors() as $proc) {
          $pa = $proc->runsOnPipes();
          if (!empty($pa)) {
            foreach ($pa as $pid) {
              $children[$pipe->id()][] = $pid;
              if (!isset($parents[$pid])) $parents[$pid] = [];
              $parents[$pid][] = $pipe->id();
            }
          }
        }
      }

      $descendants = $children;
      foreach ($descendants as $pid) {
        do {
          $oldlen = count($descendants[$pid]);
          foreach ($descendants[$pid] as $cpid) {
            $descendants[$pid] = array_merge($descendants[$pid], $descendants[$cpid]);
          }
          $descendants[$pid] = array_values(array_unique($descendants[$pid]));
          $newlen = count($descendants[$pid]);
        } while ($oldlen < $newlen);
      }
      
      $ancestors = [];
      foreach ($ancestors as $pid) {
        do {
          $oldlen = count($ancestors[$pid]);
          foreach ($ancestors[$pid] as $cpid) {
            $ancestors[$pid] = array_merge($ancestors[$pid], $ancestors[$cpid]);
          }
          $ancestors[$pid] = array_values(array_unique($ancestors[$pid]));
          $newlen = count($ancestors[$pid]);
        } while ($oldlen < $newlen);
      }
      
      $this->hierarchy = [
        'children' => $children,
        'parents' => $parents,
        'descendants' => $descendants,
        'ancestors' => $ancestors,
      ];

    }

    return $this->hierarchy;

  }

}
