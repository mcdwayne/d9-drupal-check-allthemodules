<?php
/**
 * @file
 * contains Drupal\bulk_content_operation\FieldValueBuilderServices
 */
namespace Drupal\bulk_content_operation;

use Drupal\file\Entity\File;

class FieldValueBuilderServices {
	public static function fileFieldValueBuilder($machine_name, array $importValues, $assetfoldername, $type) {
		$return_value = [];
		$machine_name_name = $machine_name.'_name';
		if (!empty($importValues[$machine_name_name])) {
			$baseurl = file_create_url(file_default_scheme()."://");
			if($assetfoldername) {
				$imagepath = $baseurl."Excels/Type/".$assetfoldername."/".$importValues[$machine_name_name];
				$desimagepath= "public://".$importValues[$machine_name_name];
				$data = file_get_contents($imagepath);
				$file = file_save_data($data, $desimagepath, FILE_EXISTS_REPLACE);
				$fid = $file->id();
			} else {
				$fid = FieldValueBuilderServices::withoutAssetGetFileObject($machine_name, $importValues);
			}
			
			$return_value = FieldValueBuilderServices::setFileFieldValueResponse($machine_name, $fid, $importValues, $type);
		}
		
		return $return_value;
	}
	
	public static function entityReferenceFieldValueBuilder($machine_name, array $importValues) {
		$machine_name_target_id = $machine_name.'_target_id';
		if(!empty($importValues[$machine_name_target_id])) {
			return [
					'target_id' => $importValues[$machine_name_target_id],
			];
		} else {
			return [];
		}
	}
	
	public static function textWithSummaryFieldValueBuilder($machine_name, array $importValues) {
		$machine_name_summary = $machine_name.'_summary';
		$machine_name_format = $machine_name.'_format';
		if (!empty($importValues[$machine_name])) {
			return [
					'value' => $importValues[$machine_name],
					'summary' => $importValues[$machine_name_summary],
					'format' => $importValues[$machine_name_format]
			];
		}else {
			return [];
		}
	}
	
	public static function defaultFieldValueBuilder($machine_name, array $importValues) {
		return $importValues[$machine_name];
	}
	
	public static function linkFieldValueBuilder($machine_name, array $importValues) {
		$machine_name_uri = $machine_name.'_uri';
		$machine_name_title = $machine_name.'_title';
		return [
				'uri' => $importValues[$machine_name_uri],
				'title' => $importValues[$machine_name_title]
		];
	}
	
	protected static function withoutAssetGetFileObject($machine_name, array $importValues) {
		$fid = FALSE;
		$tid = $machine_name.'_target_id';
		if(isset($importValues[$tid]) && $importValues[$tid] != '') {
			$file = File::load($importValues[$tid]);
			if(isset($file)) {
				$fid = $file->id();
				return $fid;
			}
		}
		
		return $fid;
	}
	
	protected static function setFileFieldValueResponse($machine_name, $fid, array $importValues, $type) {
		$result_value = [];
		$machine_name_alt = $machine_name.'_alt';
		$machine_name_title = $machine_name.'_title';
		$machine_name_display = $machine_name.'_display';
		$machine_name_description = $machine_name.'_description';
		switch ($type) {
			case 'image' :
				$result_value = [
					'target_id' => $fid,
					'alt' => $importValues[$machine_name_alt],
					'title' => $importValues[$machine_name_title],
				];
				break;
			case 'file' :
				$result_value =   [
					'target_id' => $fid,
					'display' => $importValues[$machine_name_display],
					'description' => $importValues[$machine_name_description],
				];
				break;
			default:
				$result_value = [];
		}
		return $result_value;
	}
}