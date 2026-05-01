<?php

namespace Deployer;

require __DIR__ . '/deploy_test.php';

task('deploy:update_code', function () {
    upload(__FIXTURES__ . '/project/', '{{release_path}}');
});
