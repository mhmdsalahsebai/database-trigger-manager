

document.addEventListener("DOMContentLoaded", function() {

    // Codemirror
    if (typeof cm_settings !== 'undefined') {
        const triggers = this.querySelectorAll(".trigger-action-statement");
        const hiddenInput = document.getElementById('sql_command');

        // init codemirror
        triggers.forEach(trigger => {
            const editor = wp.codeEditor.initialize(trigger, cm_settings);
            // Listen for changes in the CodeMirror editor
            editor.codemirror.on('change', function(instance) {
                // Update the value of the hidden input field
                console.log(editor.codemirror.getValue());
                hiddenInput.value = editor.codemirror.getValue();
            });
        });

    }

    // Search

    const searchInput = document.getElementById('trigger-search');
    const table = document.getElementById('trigger-table');

    // Attach event listener to search input field
    searchInput.addEventListener('input', function() {
        // Get search query
        const query = this.value.trim().toLowerCase();

        // Get table rows
        const rows = table.querySelectorAll('tbody tr');

        // Loop through table rows
        for (let i = 0; i < rows.length; i++) {
            // Get trigger name cell (adjust index as needed)
            console.log("rows[i]", rows[i])

            const triggerNameCell = rows[i].querySelector('.trigger-name-column'); // Assuming trigger name is in the 5th column
            console.log("triggerNameCell", triggerNameCell)
            // Get trigger name text content
            const triggerName = triggerNameCell.textContent.trim().toLowerCase();

            // Toggle row visibility based on search query match
            rows[i].style.display = triggerName.includes(query) ? '' : 'none';
        }
    });
});
