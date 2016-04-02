/**
 * Created by minimus on 10.07.2015.
 */
tinyMCEPopup.requireLangPack();

function init() {
  tinyMCEPopup.resizeToInnerSize();

  TinyMCE_EditableSelects.init();
}

function insertSamProCode($) {
  var
    tid = $('#sam_id').val(),
    tags = ($('#sam_codes').is(':checked')) ? 'true' : 'false',
    sc = '[sam_pro id="' + tid + '" codes="' + tags + '"]';

  window.tinyMCE.activeEditor.execCommand('mceInsertContent', false, sc);
  tinyMCEPopup.editor.execCommand('mceRepaint');
  tinyMCEPopup.close();
}

tinyMCEPopup.onInit.add(init);
