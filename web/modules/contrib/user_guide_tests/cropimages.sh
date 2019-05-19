#!/bin/bash

# Run this script from the directory of images to be cropped

for fname in $(ls *.png)
do
    echo "Cropping $fname"
    convert $fname -trim +repage $fname
done
