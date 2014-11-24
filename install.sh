#!/bin/bash

WORD_PRESS_ROOT=$(dirname $0)

# move original file
cp ${WORD_PRESS_ROOT}/index.php{,.org}
# install extend file
cp ${WORD_PRESS_ROOT}/extend/index.php ${WORD_PRESS_ROOT}/index.php
