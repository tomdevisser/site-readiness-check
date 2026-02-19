(function () {
  const addButton = document.getElementById("src-add-check");
  const tbody = document.getElementById("src-checks-body");
  const template = document.getElementById("src-check-row-template");
  const exportButton = document.getElementById("src-export-checks");
  const importFile = document.getElementById("src-import-file");

  if (!addButton || !tbody || !template) {
    return;
  }

  function getRowIndex(row) {
    const el = row.querySelector(".src-type-select");
    return el.name.match(/src_checks\[([^\]]+)\]/)[1];
  }

  function getNextIndex() {
    const rows = tbody.querySelectorAll(".src-check-row");
    let max = -1;

    rows.forEach(function (row) {
      const input = row.querySelector("input[name]");
      if (!input) {
        return;
      }

      const match = input.name.match(/src_checks\[(\d+)\]/);
      if (match) {
        max = Math.max(max, parseInt(match[1], 10));
      }
    });

    return max + 1;
  }

  function updateNameFields(row) {
    const type = row.getAttribute("data-check-type");
    const optionSelect = row.querySelector(".src-name-option");
    const constantInput = row.querySelector(".src-name-constant");
    const pluginInput = row.querySelector(".src-name-plugin");
    const nameAttr = "src_checks[" + getRowIndex(row) + "][name]";

    optionSelect.removeAttribute("name");
    constantInput.removeAttribute("name");
    pluginInput.removeAttribute("name");

    if (type === "option") {
      optionSelect.setAttribute("name", nameAttr);
    } else if (type === "constant") {
      constantInput.setAttribute("name", nameAttr);
    } else if (type === "plugin") {
      pluginInput.setAttribute("name", nameAttr);
    }
  }

  function updateValueFields(row) {
    const type = row.getAttribute("data-check-type");
    const valueCell = row.querySelector(".src-col-value");
    const valueType = valueCell.getAttribute("data-value-type");
    const textInput = valueCell.querySelector(".src-value-text");
    const boolSelect = valueCell.querySelector(".src-value-boolean");
    const pluginStatusSelect = valueCell.querySelector(
      ".src-value-plugin-status",
    );
    const valueAttr = "src_checks[" + getRowIndex(row) + "][value]";

    textInput.removeAttribute("name");
    boolSelect.removeAttribute("name");
    pluginStatusSelect.removeAttribute("name");

    if (type === "plugin") {
      pluginStatusSelect.setAttribute("name", valueAttr);
    } else if (valueType === "boolean") {
      boolSelect.setAttribute("name", valueAttr);
    } else {
      textInput.setAttribute("name", valueAttr);
    }

    textInput.type = valueType === "integer" ? "number" : "text";
    textInput.placeholder =
      valueType === "integer" ? "e.g. 0" : "e.g. development";
  }

  function bindTypeSelect(select) {
    select.addEventListener("change", function () {
      const row = select.closest(".src-check-row");
      row.setAttribute("data-check-type", select.value);
      updateNameFields(row);
      updateValueFields(row);
    });
  }

  function bindValueTypeSelect(select) {
    select.addEventListener("change", function () {
      const cell = select.closest(".src-col-value");
      cell.setAttribute("data-value-type", select.value);
      updateValueFields(cell.closest(".src-check-row"));
    });
  }

  function bindRemoveButton(button) {
    button.addEventListener("click", function () {
      const row = button.closest(".src-check-row");
      if (row) {
        row.remove();
      }
    });
  }

  function bindRow(row) {
    bindRemoveButton(row.querySelector(".src-remove-check"));
    bindTypeSelect(row.querySelector(".src-type-select"));
    bindValueTypeSelect(row.querySelector(".src-value-type-select"));
  }

  tbody.querySelectorAll(".src-check-row").forEach(bindRow);

  addButton.addEventListener("click", function () {
    const index = getNextIndex();
    const html = template.innerHTML.replace(/__INDEX__/g, index);

    const temp = document.createElement("tbody");
    temp.innerHTML = html;

    const newRow = temp.querySelector("tr");
    if (newRow) {
      tbody.appendChild(newRow);
      bindRow(newRow);
      newRow.querySelector("input").focus();
    }
  });

  function collectChecks() {
    const checks = [];
    tbody.querySelectorAll(".src-check-row").forEach(function (row) {
      const index = getRowIndex(row);
      const type = row.getAttribute("data-check-type");
      const label = row.querySelector(
        'input[name="src_checks[' + index + '][label]"]',
      ).value;
      let name;
      if (type === "option") {
        name = row.querySelector(".src-name-option").value;
      } else if (type === "plugin") {
        name = row.querySelector(".src-name-plugin").value;
      } else {
        name = row.querySelector(".src-name-constant").value;
      }
      const valueCell = row.querySelector(".src-col-value");
      const valueType = valueCell.getAttribute("data-value-type");
      let value;
      if (type === "plugin") {
        value = valueCell.querySelector(".src-value-plugin-status").value;
      } else if (valueType === "boolean") {
        value = valueCell.querySelector(".src-value-boolean").value;
      } else {
        value = valueCell.querySelector(".src-value-text").value;
      }
      const severity = row.querySelector(
        'select[name="src_checks[' + index + '][severity]"]',
      ).value;

      if (name) {
        checks.push({
          label: label,
          type: type,
          name: name,
          value: value,
          value_type: valueType,
          severity: severity,
        });
      }
    });
    return checks;
  }

  function createRow(index, check) {
    const html = template.innerHTML.replace(/__INDEX__/g, index);
    const temp = document.createElement("tbody");
    temp.innerHTML = html;
    const row = temp.querySelector("tr");
    if (!row) {
      return null;
    }

    row.setAttribute("data-check-type", check.type || "option");
    row.querySelector(".src-type-select").value = check.type || "option";
    row.querySelector('input[name="src_checks[' + index + '][label]"]').value =
      check.label || "";

    if (check.type === "plugin") {
      row.querySelector(".src-name-plugin").value = check.name || "";
    } else if (check.type === "constant") {
      row.querySelector(".src-name-constant").value = check.name || "";
    } else {
      row.querySelector(".src-name-option").value = check.name || "";
    }

    const valueCell = row.querySelector(".src-col-value");
    valueCell.setAttribute("data-value-type", check.value_type || "string");
    row.querySelector(".src-value-type-select").value =
      check.value_type || "string";

    if (check.type === "plugin") {
      valueCell.querySelector(".src-value-plugin-status").value =
        check.value || "active";
    } else if (check.value_type === "boolean") {
      valueCell.querySelector(".src-value-boolean").value = check.value || "1";
    } else {
      valueCell.querySelector(".src-value-text").value = check.value || "";
    }

    row.querySelector(
      'select[name="src_checks[' + index + '][severity]"]',
    ).value = check.severity || "recommended";

    updateNameFields(row);
    updateValueFields(row);
    bindRow(row);

    return row;
  }

  if (exportButton) {
    exportButton.addEventListener("click", function () {
      const checks = collectChecks();
      const blob = new Blob([JSON.stringify(checks, null, 2)], {
        type: "application/json",
      });
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = "site-readiness-checks.json";
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

  if (importFile) {
    importFile.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (!file) {
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        let checks;
        try {
          checks = JSON.parse(event.target.result);
        } catch (err) {
          alert("Invalid JSON file.");
          return;
        }

        if (!Array.isArray(checks)) {
          alert("Invalid format: expected an array of checks.");
          return;
        }

        tbody.innerHTML = "";

        checks.forEach(function (check, i) {
          const row = createRow(i, check);
          if (row) {
            tbody.appendChild(row);
          }
        });
      };
      reader.readAsText(file);
      importFile.value = "";
    });
  }
})();
