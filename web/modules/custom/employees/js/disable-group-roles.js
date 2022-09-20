// Ensure that the user only gets either Admin or Editor role
function uncheckAdminOrEditor(event) {
  switch(event.target.id) {
    case "edit-group-roles-employee-admin":
      document.getElementById("edit-group-roles-employee-editor").checked = false;
      break;
    case "edit-group-roles-employee-editor":
      document.getElementById("edit-group-roles-employee-admin").checked = false;
      break;
    case "edit-group-roles-private-admin":
      document.getElementById("edit-group-roles-private-editor").checked = false;
      break;
    case "edit-group-roles-private-editor":
      document.getElementById("edit-group-roles-private-admin").checked = false;
      break;
  }
}

(function($){
  if(! drupalSettings.isAdmin) {
    $("div.form-item--group-roles-private-employee").hide();
    $("div.form-item--group-roles-private-following").hide();
    $("div.form-item--group-roles-employee-employee").hide();
    $("div.form-item--group-roles-employee-following").hide();
  }

  var adminCheckbox = document.getElementById("edit-group-roles-employee-admin");
  if(adminCheckbox)
    adminCheckbox.addEventListener('click', uncheckAdminOrEditor);
  
  var editorCheckbox = document.getElementById("edit-group-roles-employee-editor");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', uncheckAdminOrEditor);

  adminCheckbox = document.getElementById("edit-group-roles-private-admin");
  if(adminCheckbox)
    adminCheckbox.addEventListener('click', uncheckAdminOrEditor);
  
  editorCheckbox = document.getElementById("edit-group-roles-private-editor");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', uncheckAdminOrEditor);
})(jQuery);