Libraries are embedded via Git subtree

[Twitter-library :: tmhOAuth]
git remote add remote-tmhOAuth git://github.com/themattharris/tmhOAuth.git
git subtree add --prefix dataimport/php/library/tmhOAuth remote-tmhOAuth 0.8.1 --squash
