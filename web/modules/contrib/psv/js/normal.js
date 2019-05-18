Drupal.behaviors.NormalImage = {
  attach: function (context, settings) {
    const passwordInput = document.querySelector('#edit-pass-pass1');
    const canvasWrapper = document.querySelector('.canvas-wrap');
    const canvas = canvasWrapper.querySelector('canvas');
    const poster = document.querySelector('.poster');
    const posterImg = poster.style.backgroundImage.match(/\((.*?)\)/)[1].replace(/('|")/g,'');

    // The following code was taken and modified from http://jsfiddle.net/u6apxgfk/390/
    // (C) Ken Fyrstenberg, Epistemex, License: CC3.0-attr

    // and merged with https://codepen.io/bassta/pen/OPVzyB?editors=1010

    const ctx = canvas.getContext('2d');
    const img = new Image();
    let imgRatio;
    let wrapperRatio;
    let newWidth;
    let newHeight;
    let newX;
    let newY;

    let psIndicator = 1;

    img.src = posterImg;
    img.onload = () => {
        const imgWidth = img.width;
        const imgHeight = img.height;
        imgRatio = imgWidth / imgHeight;
        setCanvasSize();
        render();
    };

    const setCanvasSize = () => {
        canvas.width = canvasWrapper.offsetWidth;
        canvas.height = canvasWrapper.offsetHeight;
    };

    const render = () => {
        const w = canvasWrapper.offsetWidth;
        const h = canvasWrapper.offsetHeight;

        newWidth = w;
        newHeight = h;
        newX = 0;
        newY = 0;
        wrapperRatio = newWidth / newHeight;

        if ( wrapperRatio > imgRatio ) {
            newHeight = Math.round(w / imgRatio);
            newY = (h - newHeight) / 2;
        }
        else {
            newWidth = Math.round(h * imgRatio);
            newX = (w - newWidth) / 2;
        }

        const size = psIndicator * 0.01;

        // turn off image smoothing - this will give the pixelated effect
        ctx.mozImageSmoothingEnabled = size === 1 ? true : false;
        ctx.webkitImageSmoothingEnabled = size === 1 ? true : false;
        ctx.imageSmoothingEnabled = size === 1 ? true : false;

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        // draw original image to the scaled size
        ctx.drawImage(img, 0, 0, w*size, h*size);
        // then draw that scaled image thumb back to fill canvas
        // As smoothing is off the result will be pixelated
        ctx.drawImage(canvas, 0, 0, w*size, h*size, newX, newY, newWidth+.05*w, newHeight+.05*h);
    };

    window.addEventListener('resize', () => {
        setCanvasSize();
        render();
    });

    passwordInput.addEventListener('input', () => {
        psIndicator = Math.round(jQuery('.password-strength__indicator').width() / jQuery('.password-strength__indicator').parent().width() * 100);
        if (psIndicator == 0) {
            psIndicator = 1;
        };
        if ( psIndicator != 100 ) {
            psIndicator -= psIndicator/100*50;
        }
        render();
    });
  }
};
