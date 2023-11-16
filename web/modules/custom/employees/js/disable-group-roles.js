// Ensure that the user only gets one of Admin, Editor, or Reviewer roles
function enforceUserRoleSelection(event) {
  switch(event.target.id) {
    case "edit-group-roles-employee-admin":
      document.getElementById("edit-group-roles-employee-editor").checked = false;
      document.getElementById("edit-group-roles-employee-reviewer").checked = false;
      break;
    case "edit-group-roles-employee-editor":
      document.getElementById("edit-group-roles-employee-admin").checked = false;
      document.getElementById("edit-group-roles-employee-reviewer").checked = false;
      break;
    case "edit-group-roles-employee-reviewer":
      document.getElementById("edit-group-roles-employee-admin").checked = false;
      document.getElementById("edit-group-roles-employee-editor").checked = false;
      break;
    case "edit-group-roles-private-admin":
      document.getElementById("edit-group-roles-private-editor").checked = false;
      document.getElementById("edit-group-roles-private-reviewer").checked = false;
      break;
    case "edit-group-roles-private-editor":
      document.getElementById("edit-group-roles-private-admin").checked = false;
      document.getElementById("edit-group-roles-private-reviewer").checked = false;
      break;
    case "edit-group-roles-private-reviewer":
      document.getElementById("edit-group-roles-private-admin").checked = false;
      document.getElementById("edit-group-roles-private-editor").checked = false;
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
    adminCheckbox.addEventListener('click', enforceUserRoleSelection);
  
  var editorCheckbox = document.getElementById("edit-group-roles-employee-editor");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', enforceUserRoleSelection);

  var editorCheckbox = document.getElementById("edit-group-roles-employee-reviewer");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', enforceUserRoleSelection);

  adminCheckbox = document.getElementById("edit-group-roles-private-admin");
  if(adminCheckbox)
    adminCheckbox.addEventListener('click', enforceUserRoleSelection);
  
  editorCheckbox = document.getElementById("edit-group-roles-private-editor");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', enforceUserRoleSelection);
  
  editorCheckbox = document.getElementById("edit-group-roles-private-reviewer");
  if(editorCheckbox)
    editorCheckbox.addEventListener('click', enforceUserRoleSelection);
})(jQuery);