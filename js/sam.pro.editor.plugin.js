/**
 * Created by minimus on 10.07.2015.
 */
(function () {
  var data = samProOptions.data,
    tinyMceUrl = samProOptions.url,
    jqUrl = samProOptions.jqUrl,
    strings = samProOptions.strings;

  tinymce.PluginManager.requireLangPack('spButton');

  tinymce.create('tinymce.plugins.spButton', {
    init: function (ed, url) {
      this.editor = ed;

      ed.addCommand('samProObj', function () {
        ed.windowManager.open({
          file: url + '/sam-pro-dialog.html',
          width: 450 + parseInt(ed.getLang('spButton.delta_width', 0)),
          height: 280 + parseInt(ed.getLang('spButton.delta_height', 0)),
          inline: 1
        }, {
          plugin_url: url,
          data: data,
          url: tinyMceUrl,
          jqUrl: jqUrl,
          strings: strings
        })
      });

      ed.addButton('spButton', {title: 'Insert Advertisement', cmd: 'samProObj', image: url + '/img/sam-icon.png'});
    },

    getInfo: function () {
      return {
        longname: 'SAM Pro',
        author: 'minimus',
        authorurl: 'http://blogcoding.ru/',
        infourl: 'http://uncle-sam.info/',
        version: "1.0.0.10"
      };
    }
  });

  tinymce.PluginManager.add('spButton', tinymce.plugins.spButton);
})();