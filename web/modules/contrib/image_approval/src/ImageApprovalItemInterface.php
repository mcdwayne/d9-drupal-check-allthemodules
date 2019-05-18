<?php

namespace Drupal\image_approval;

/**
 * Interface definition for Image Approval items.
 */
interface ImageApprovalItemInterface {

  /**
 * Image is awaiting approval and added to the queue.
 */

  const IMAGE_APPROVAL_PENDING = -1;

  /**
 * Image has been approved.
 */
  const IMAGE_APPROVAL_APPROVED = 1;

  /**
 * Image has been disapproved.
 */
  const IMAGE_APPROVAL_DISAPPROVED = 0;

}
