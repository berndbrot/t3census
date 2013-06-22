Libraries are embedded via Git subtree

[Twitter-library :: tmhOAuth]
git remote add remote-tmhOAuth git://github.com/themattharris/tmhOAuth.git
git subtree add --prefix dataimport/php/library/tmhOAuth remote-tmhOAuth 0.8.1 --squash

[PHPUnit]
git remote add remote-PHPUnit git://github.com/sebastianbergmann/phpunit.git
git subtree add --prefix library/php/PHPUnit remote-PHPUnit 3.7.21 --squash

[URL Parse :: Purl]
git remote add remote-Purl git://github.com/jwage/purl.git
git subtree add --prefix library/php/Purl remote-Purl v0.0.2 --squash