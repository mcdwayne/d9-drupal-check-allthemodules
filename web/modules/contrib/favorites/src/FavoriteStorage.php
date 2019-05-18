<?php

/**
 * @file
 * Contains \Drupal\favorites\FavoriteStorage.
 */

namespace Drupal\favorites;	
 
 class FavoriteStorage {	 
	
	/**
	* {@inheritdoc}
	*/
	static function delete($fid){
		db_delete('favorites')
		->condition('fid',$fid)
		->execute();
	}
	
	/**
	* {@inheritdoc}
	*/
	static function getFavorites($uid){		
                $result = db_query('select * from {favorites} where uid = :uip order by timestamp DESC', array(':uip'=> $uid));
		return $result;
	}
	
        /**
	* {@inheritdoc}
	*/
        static function getFav($fid){
            return db_query('select * from {favorites} where fid=:fid', array(':fid'=>$fid))->fetchObject();
        }
        
        /**
	* {@inheritdoc}
	*/
	static function deleteFavorite($fid){
		db_delete('favorites')
			->condition('fid', $fid)			
			->execute();
	}
	/**
	* {@inheritdoc}
	*/
	static function deleteFav($uid, $path, $query){
		db_delete('favorites')
			->condition('uid', $uid)
			->condition('path', $path)
			->condition('query', $query)
			->execute();
	}
        
	/**
	* {@inheritdoc}
	*/
	static function addFav($uid, $path, $query, $title){
		db_insert('favorites')
			->fields(array(
			  'uid' => $uid,
			  'path' => $path,
			  'query' => $query,
			  'title' => $title,
			  'timestamp' => REQUEST_TIME,
			))
			->execute();
	}
 }
 
