/**
 * @file
 * Attaches behaviors for Vimeo Upload.
 */

(($, Drupal) => {
  Drupal.behaviors.vimeoUploadBehavior = {
    attach(context, settings) {
      /* @todo Standardize javascript usage (ES5,ES6, jQuery). */

      const accessToken = settings.access_token;

      /* @todo move contents in settings */
      const textError = Drupal.t("Error");
      const textSuccess = Drupal.t("Upload Successful");
      const textPrivate = Drupal.t(
        "Note that this video has been set as private, so you must be logged in with the Vimeo channel account to view it."
      );
      const textCopyUrl = Drupal.t("You can now copy the URL.");
      const textCopied = Drupal.t("Copied!");

      /**
       * Display the progressbar
       */
      function showProgress() {
        document.getElementById("drop_zone").classList.remove("is-hovered");
        document.getElementById("drop_zone").classList.add("is-uploading");
      }

      /**
       * Update the progress bar.
       *
       * @param progress
       */
      function updateProgress(progress) {
        progress = Math.floor(progress * 100);
        const progressBar = document.getElementById("progress-bar");
        const progressPercentage = document.getElementById(
          "progress-percentage"
        );
        progressBar.setAttribute("style", `width:${progress}%`);
        progressPercentage.innerHTML = `&nbsp;${progress}%`;
      }

      /**
       * Shows a message.
       *
       * @param html
       * @param type
       */
      function showMessage(html, type) {
        /* display alert message */
        const element = document.createElement("div");
        element.setAttribute("class", `alert alert-${type || "success"}`);
        element.innerHTML = html;
        const results = document.getElementById("results");
        results.appendChild(element);
        const progressResults = document.getElementById("progress-results");
        document.getElementById("drop_zone").classList.add("is-done");
        document.getElementById("drop_zone").classList.remove("is-uploading");
      }

      /**
       * Called when files are dropped on to the drop target or selected by the browse button.
       * For each file, uploads the content to Drive & displays the results when complete.
       *
       * @param evt
       */

      function handleFileSelect(evt) {
        evt.stopPropagation();
        evt.preventDefault();

        const files = evt.dataTransfer
          ? evt.dataTransfer.files
          : $(this).get(0).files;
        const results = document.getElementById("results");

        // Clear the results div.
        while (results.hasChildNodes()) results.removeChild(results.firstChild);
        // Clear the video Url and hide it.
        const videoUrlInput = document.getElementById("videoUrl");
        videoUrlInput.value = "";

        // Rest the progress bar and show it.
        showProgress();
        updateProgress(0);

        // Disable the previous button
        $(".vimeo-upload__prev").attr("disabled", true);

        // Instantiate Vimeo Uploader.
        new VimeoUpload({
          name: document.getElementById("videoName").value,
          description: document.getElementById("videoDescription").value,
          private: document.getElementById("make_private").checked,
          file: files[0],
          token: accessToken,
          upgrade_to_1080: document.getElementById("upgrade_to_1080").checked,
          onError(data) {
            showMessage(
              `<strong>${textError}</strong>: ${JSON.parse(data).error}`,
              "danger"
            );
          },
          onProgress(data) {
            updateProgress(data.loaded / data.total);
          },
          onComplete(videoId, index) {
            $(".vimeo-upload__prev").removeAttr("disabled");
            let url = `https://vimeo.com/${videoId}`;

            if (index > -1) {
              // The metadata contains all of the uploaded video(s) details see:
              // https://developer.vimeo.com/api/endpoints/videos#/{video_id}
              url = this.metadata[index].link;

              // Add stringify the json object for displaying in a text area.
              const pretty = JSON.stringify(this.metadata[index], null, 2);
            }

            // Display the success message and the video url.
            let successMessage = `<p><strong>${textSuccess}</strong>.</p>`;
            if (document.getElementById("make_private").checked) {
              successMessage += `<p>${textPrivate}</p>`;
            }
            successMessage += `<p>${textCopyUrl}</p>`;
            showMessage(successMessage);
            videoUrlInput.value = url;
          }
        }).upload();
      }

      /**
       * Dragover handler to set the drop effect.
       *
       * @param evt
       */
      function handleDragOver(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        evt.dataTransfer.dropEffect = "copy";
        this.classList.add("is-hovered");
      }

      /**
       * Dragleave handler to unset the drop effect.
       *
       * @param evt
       */
      function handleDragLeave(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        this.classList.remove("is-hovered");
      }

      /**
       * Check if all required fields are filled.
       */
      function validateFormGroup() {
        const $searchInputs = $(".vimeo-upload__required");
        const $nextButton = $(".vimeo-upload__next");

        let isValid = true;
        $searchInputs.each(function() {
          if ($(this).val() === "") isValid = false;
        });

        isValid
          ? $nextButton.removeAttr("disabled")
          : $nextButton.attr("disabled", true);
      }

      /**
       * Go to next tab.
       */
      function goToNextTab() {
        $step = $(this).data("step");
        $nextStep = $step + 1;

        document.getElementById("drop_zone").classList.remove("is-done");
        document.getElementById("drop_zone").classList.remove("is-uploading");

        $(`.vimeo-upload__group[data-step="${$step}"]`).removeClass(
          "is-visible"
        );
        $(`.vimeo-upload__group[data-step="${$nextStep}"]`).addClass(
          "is-visible"
        );
      }

      /**
       * Go to previous tab.
       */
      function goToPrevTab() {
        $step = $(this).data("step");
        $prevStep = $step - 1;

        $(`.vimeo-upload__group[data-step="${$step}"]`).removeClass(
          "is-visible"
        );
        $(`.vimeo-upload__group[data-step="${$prevStep}"]`).addClass(
          "is-visible"
        );
      }

      /**
       * Copy url.
       */
      function copyUrl() {
        $input = $(this).prev("input");
        $input.focus();
        $input.select();
        document.execCommand("copy");
        $(this).text(textCopied);
      }

      /**
       * Wire up drag & drop listeners once page loads.
       */
      $(context)
        .find(".vimeo-upload__content")
        .once("vimeoUploadBehavior")
        .each(function() {
          const dropZone = document.getElementById("drop_zone");
          const browse = document.getElementById("browse");

          $(".vimeo-upload__required").each(function() {
            $(this).keyup(validateFormGroup);
            $(this).change(validateFormGroup);
          });

          $(".vimeo-upload__next").bind("click", goToNextTab);
          $(".vimeo-upload__prev").bind("click", goToPrevTab);
          $(".vimeo-upload__url-button").bind("click", copyUrl);

          dropZone.addEventListener("dragover", handleDragOver, false);
          dropZone.addEventListener("dragleave", handleDragLeave, false);
          dropZone.addEventListener("drop", handleFileSelect, false);
          browse.addEventListener("change", handleFileSelect, false);
        });
    }
  };
})(jQuery, Drupal);
