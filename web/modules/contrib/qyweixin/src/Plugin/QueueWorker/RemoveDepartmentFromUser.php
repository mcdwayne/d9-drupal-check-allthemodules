<?php

/**
 * @file
 * Contains \Drupal\qyweixin\Plugin\QueueWorker\RemoveDepartmentFromUser.
 */

namespace Drupal\qyweixin\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\qyweixin\CorpBase;

/**
 * Remove Department from qyweixin's user.
 *
 * @QueueWorker(
 *   id = "qyweixin_remove_department_from_user",
 *   title = @Translation("Remove Department from qyweixin's user"),
 *   cron = {"time" = 60}
 * )
 */
class RemoveDepartmentFromUser extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
	$user=$item['user'];
	$role=$item['role'];
	try {
		if(empty($user)) {
			CorpBase::departmentDelete($role->getThirdPartySetting('qyweixin','departmentid'));
			\Drupal::logger('qyweixin')->info('Role !role as of department has been deleted from qyweixin.',
				array('!role'=>$role->label())
			);
		}
		else {
			$user->department=array_diff($user->department, [$role->getThirdPartySetting('qyweixin','departmentid')]);
			CorpBase::userUpdate($user);
		}
	} catch (\Exception $e)	{
		\Drupal::logger('qyweixin')->error('Syncing information of !role into qyweixin failed: !errcode: !errmsg.',
			array('!role'=>$role->label(), '!errcode'=>$e->getCode(), '!errmsg'=>$e->getMessage())
		);
	}
  }
}
?>
