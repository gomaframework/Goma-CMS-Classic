/**
 *
 * @param namespace
 * @param backtrackSpace
 */
var fileManagerController = function(namespace, backtrackSpace, differentVersions) {
    var backtrackSpaceQuery = $(backtrackSpace);
    var versions = $(differentVersions);
    var backtrackPage = 1;
    var versionPage = 1;
    var loadBacktrack = function() {
        backtrackSpaceQuery.addClass("loading");
        backtrackSpaceQuery.removeClass("has-more");
        $.ajax({
            url: namespace + "/backtrack/" + backtrackPage,
            dataType: "json"
        }).done(function(data){
            if(data.wholeCount == 0) {
                backtrackSpaceQuery.addClass("has-zero");
            }
            if(data.hasNextPage) {
                backtrackSpaceQuery.addClass("has-more");
            } else {
                backtrackSpaceQuery.removeClass("has-more");
            }

            for(var i in data.data) {
                if(data.data.hasOwnProperty(i)) {
                    if(data.data[i].representation) {
                        backtrackSpaceQuery.find("ul").append('<li>' + data.data[i].representation + '</li>')
                    } else {
                        backtrackSpaceQuery.find("ul").append('<li>' + data.data[i].classname + ': '+ data.data[i].id+'</li>')
                    }
                }
            }
        }).always(function(){
            backtrackSpaceQuery.removeClass("loading");
        }).fail(function(status, response){
            alert(response);
        });
        backtrackPage++;
    };
    var loadVersions = function() {
        versions.addClass("loading");
        versions.removeClass("has-more");
        $.ajax({
            url: namespace + "/allversions/" + versionPage,
            dataType: "json"
        }).done(function(data){
            console.log(data);
            if(data.wholeCount == 0) {
                versions.addClass("has-zero");
            }
            if(data.hasNextPage) {
                versions.addClass("has-more");
            } else {
                versions.removeClass("has-more");
            }

            for(var i in data.data) {
                if(data.data.hasOwnProperty(i)) {
                    var isThis = data.data[i].isThis ? ' <span class="this">' + lang("this_version") + '</span>' : '';
                    versions.find("ul").append('<li><a href="'+data.data[i].links.manage+'">' + data.data[i].filename + ''+isThis+'</a></li>')
                }
            }
        }).always(function(){
            versions.removeClass("loading");
        }).fail(function(status, response){
            alert(response);
        });
        versionPage++;
    };
    $(loadBacktrack);
    $(loadVersions);
};
