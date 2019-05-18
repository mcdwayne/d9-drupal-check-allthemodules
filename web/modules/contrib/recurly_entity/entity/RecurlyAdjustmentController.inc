<?php

class RecurlyAdjustmentController extends RecurlyAPIController {

  /**
   * Return the Recurly class that should be used to interact with Recurly.
   *
   * @return Recurly_Adjustment
   *   The Recurly_Adjustment class.
   */
  public function resourceClass() {
    return new Recurly_Adjustment();
  }
}
