function sc(url) {
    if (url.search("leadid") > 0) {
        console.log(url);
        var urlSplit = url.split('&');
        var data = urlSplit[1];

        document.cookie = data;
    }
}