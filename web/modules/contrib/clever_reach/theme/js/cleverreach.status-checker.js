var CleverReach = window.CleverReach || {};

(function () {
  'use strict';

  const config = {};

  function disable(el) {
    el.classList.add(config.disabledCls);
  }

  function enable(el) {
    el.classList.remove(config.disabledCls);
  }

  function hide(el) {
    el.classList.add(config.hiddenCls);
  }

  function show(el) {
    el.classList.remove(config.hiddenCls);
  }

  function setPendingState(el) {
    let i;

    for (i = 0; i < config.inProgressStatusClasses.length; i++) {
      el.classList.remove(config.inProgressStatusClasses[i]);
    }

    for (i = 0; i < config.doneStatusClasses.length; i++) {
      el.classList.remove(config.doneStatusClasses[i]);
    }

    for (i = 0; i < config.pendingStatusClasses.length; i++) {
      el.classList.add(config.pendingStatusClasses[i]);
    }
  }

  function setFinishedState(el) {
    let i;

    for (i = 0; i < config.pendingStatusClasses.length; i++) {
      el.classList.remove(config.pendingStatusClasses[i]);
    }

    for (i = 0; i < config.inProgressStatusClasses.length; i++) {
      el.classList.remove(config.inProgressStatusClasses[i]);
    }

    for (i = 0; i < config.doneStatusClasses.length; i++) {
      el.classList.add(config.doneStatusClasses[i]);
    }
  }

  function setInProgressState(el) {
    let i;

    for (i = 0; i < config.pendingStatusClasses.length; i++) {
      el.classList.remove(config.pendingStatusClasses[i]);
    }

    for (i = 0; i < config.doneStatusClasses.length; i++) {
      el.classList.remove(config.doneStatusClasses[i]);
    }

    for (i = 0; i < config.inProgressStatusClasses.length; i++) {
      el.classList.add(config.inProgressStatusClasses[i]);
    }
  }

  function setTaskInProgress(taskName, progress) {
    let i;

    const taskEls = document.querySelectorAll(`${config.baseSelector} [data-task="${taskName}"]`);
    const taskStatusEls = document.querySelectorAll(`${config.baseSelector} [data-status="${taskName}"]`);
    const taskProgressEls = document.querySelectorAll(`${config.baseSelector} [data-progress="${taskName}"]`);
    for (i = 0; i < taskEls.length; i++) {
      enable(taskEls[i]);
    }

    for (i = 0; i < taskStatusEls.length; i++) {
      setInProgressState(taskStatusEls[i]);
    }

    for (i = 0; i < taskProgressEls.length; i++) {
      taskProgressEls[i].innerHTML = `${parseInt(progress)}%`;
      show(taskProgressEls[i]);
    }
  }

  function setTaskFinished(taskName) {
    let i;

    const taskEls = document.querySelectorAll(`${config.baseSelector} [data-task="${taskName}"]`);
    const taskStatusEls = document.querySelectorAll(`${config.baseSelector} [data-status="${taskName}"]`);
    const taskProgressEls = document.querySelectorAll(`${config.baseSelector} [data-progress="${taskName}"]`);
    for (i = 0; i < taskEls.length; i++) {
      enable(taskEls[i]);
    }

    for (i = 0; i < taskStatusEls.length; i++) {
      setFinishedState(taskStatusEls[i]);
    }

    for (i = 0; i < taskProgressEls.length; i++) {
      taskProgressEls[i].innerHTML = '100%';
      taskProgressEls[i].classList.add(config.finishedStatus);
    }
  }

  function setTaskPending(taskName) {
    let i;

    const taskEls = document.querySelectorAll(`${config.baseSelector} [data-task="${taskName}"]`);
    const taskStatusEls = document.querySelectorAll(`${config.baseSelector} [data-status="${taskName}"]`);
    const taskProgressEls = document.querySelectorAll(`${config.baseSelector} [data-progress="${taskName}"]`);
    for (i = 0; i < taskEls.length; i++) {
      disable(taskEls[i]);
    }

    for (i = 0; i < taskStatusEls.length; i++) {
      setPendingState(taskStatusEls[i]);
    }

    for (i = 0; i < taskProgressEls.length; i++) {
      hide(taskProgressEls[i]);
    }
  }

  function refreshTask(taskName, taskStatus) {
    switch (taskStatus.status) {
      case config.inProgressStatus:
        setTaskInProgress(taskName, taskStatus.progress);
        break;
      case config.finishedStatus:
        setTaskFinished(taskName);
        break;
      default:
        setTaskPending(taskName);
        break;
    }
  }

  function refreshTasks(taskStatuses) {
    let taskName; let
      taskStatus;
    for (taskName in taskStatuses) {
      if (taskStatuses.hasOwnProperty(taskName)) {
        taskStatus = taskStatuses[taskName];
        refreshTask(taskName, taskStatus);
      }
    }
  }

  function checkStatus() {
    CleverReach.Ajax.post(config.statusCheckUrl, null, (response) => {
      if (response.taskStatuses) {
        refreshTasks(response.taskStatuses);
      }

      if (response.status !== config.finishedStatus && response.status !== config.failedStatus) {
        setTimeout(checkStatus, 500);
      }
      else {
        config.onComplete.call(config.onCompleteCallbackScope, response);
      }
    }, 'json', true);
  }

  function disableAllTasks() {
    const taskStates = {}; let
      i;

    for (i = 0; i < config.taskNames.length; i++) {
      taskStates[config.taskNames[i]] = {
        status: 'pending',
        progress: 0
      };
    }

    refreshTasks(taskStates);
  }

  function statusCheckerInit(initialConfig) {
    initialConfig = initialConfig || {};
    if (!initialConfig.statusCheckUrl) {
      throw 'CleverReach.StatusChecker: Configuration for statusCheckUrl is mandatory';
    }

    config.statusCheckUrl = initialConfig.statusCheckUrl;
    config.inProgressStatus = initialConfig.inProgressStatus || 'in_progress';
    config.finishedStatus = initialConfig.finishedStatus || 'completed';
    config.failedStatus = initialConfig.failedStatus || 'failed';
    config.onComplete = initialConfig.onComplete || function () {};
    config.onCompleteCallbackScope = initialConfig.onCompleteCallbackScope || window;
    config.taskNames = initialConfig.taskNames || ['subscriber_list', 'add_fields', 'recipient_sync'];
    config.baseSelector = initialConfig.baseSelector || '.clever-reach';
    config.disabledCls = initialConfig.disabledCls || 'disabled';
    config.hiddenCls = initialConfig.hiddenCls || 'hidden';
    config.pendingStatusClasses = initialConfig.pendingStatusClasses || ['pending'];
    config.inProgressStatusClasses = initialConfig.inProgressStatusClasses || ['in_progress'];
    config.doneStatusClasses = initialConfig.doneStatusClasses || ['done'];

    disableAllTasks();
    checkStatus();
  }

  CleverReach.StatusChecker = {
    init: statusCheckerInit
  };
}());
