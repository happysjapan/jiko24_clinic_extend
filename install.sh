#!/bin/bash

WORD_PRESS_ROOT=$(dirname $0)/../

# install extend file
cp ${WORD_PRESS_ROOT}/extend/wp-includes/option.php ${WORD_PRESS_ROOT}/wp-includes/option.php
