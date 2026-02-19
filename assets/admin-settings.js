(function () {
  const addButton = document.getElementById("src-add-check");
  const tbody = document.getElementById("src-checks-body");
  const template = document.getElementById("src-check-row-template");

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
    const nameAttr = "src_checks[" + getRowIndex(row) + "][name]";

    if (type === "option") {
      optionSelect.setAttribute("name", nameAttr);
      constantInput.removeAttribute("name");
    } else {
      constantInput.setAttribute("name", nameAttr);
      optionSelect.removeAttribute("name");
    }
  }

  function updateValueFields(row) {
    const valueCell = row.querySelector(".src-col-value");
    const valueType = valueCell.getAttribute("data-value-type");
    const textInput = valueCell.querySelector(".src-value-text");
    const boolSelect = valueCell.querySelector(".src-value-boolean");
    const valueAttr = "src_checks[" + getRowIndex(row) + "][value]";

    if (valueType === "boolean") {
      boolSelect.setAttribute("name", valueAttr);
      textInput.removeAttribute("name");
    } else {
      textInput.setAttribute("name", valueAttr);
      boolSelect.removeAttribute("name");
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
})();
