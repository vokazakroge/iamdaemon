const dropZone = document.getElementById("dropZone");
const fileInput = document.getElementById("fileInput");
const statusMsg = document.getElementById("statusMsg");

["dragenter", "dragover"].forEach((eventName) => {
    dropZone.addEventListener(
        eventName,
        (e) => {
            e.preventDefault();
            dropZone.classList.add("dragover");
        },
        false
    );
});

["dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(
        eventName,
        (e) => {
            e.preventDefault();
            dropZone.classList.remove("dragover");
        },
        false
    );
});

dropZone.addEventListener("drop", (e) => {
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener("change", (e) => {
    handleFiles(e.target.files);
});

async function handleFiles(files) {
    if (!files.length) return;

    statusMsg.textContent = "Loading...";
    statusMsg.className = "status-msg";

    for (const file of files) {
        const formData = new FormData();
        formData.append("file", file);

        try {
            const res = await fetch("/api/upload.php", {
                method: "POST",
                body: formData,
            });
            const data = await res.json();

            if (!res.ok || data.error) {
                throw new Error(data.error || "Upload failed");
            }

            statusMsg.textContent = file.name + " uploaded";
            statusMsg.className = "status-msg success";
            setTimeout(() => location.reload(), 800);
        } catch (err) {
            statusMsg.textContent = file.name + ": " + err.message;
            statusMsg.className = "status-msg error";
        }
    }
}

// Inline rename logic (double click)
document.querySelectorAll(".filename-text").forEach((span) => {
    span.addEventListener("dblclick", function() {
        const originalName = this.dataset.name;
        if (this.querySelector("input")) return;

        const input = document.createElement("input");
        input.type = "text";
        input.value = originalName;
        input.className = "rename-input";

        this.textContent = "";
        this.appendChild(input);
        input.focus();
        input.select();

        let isCanceled = false;

        const finishEdit = async() => {
            const newName = input.value.trim();

            if (isCanceled || !newName || newName === originalName) {
                this.textContent = originalName;
                return;
            }

            try {
                const res = await fetch("/api/rename.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ old_name: originalName, new_name: newName }),
                });
                const data = await res.json();

                if (!res.ok || data.error) {
                    throw new Error(data.error);
                }

                location.reload();
            } catch (err) {
                alert("Error: " + err.message);
                this.textContent = originalName;
            }
        };

        input.addEventListener("blur", finishEdit);
        input.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                input.blur();
            } else if (e.key === "Escape") {
                isCanceled = true;
                input.blur();
            }
        });
    });
});

// Delete handler
document.querySelectorAll(".btn-icon.delete").forEach((btn) => {
    btn.addEventListener("click", async() => {
        const filename = btn.dataset.file;
        if (!confirm("Delete " + filename + "?")) return;

        try {
            const res = await fetch("/api/delete.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ file: filename }),
            });
            const text = await res.text();
            const data = JSON.parse(text);

            if (!res.ok || data.error) {
                throw new Error(data.error);
            }

            location.reload();
        } catch (err) {
            alert("Error: " + err.message);
        }
    });
});

// Editor modal logic
const modal = document.getElementById("editorModal");
const title = document.getElementById("editorTitle");
const closeModal = document.getElementById("closeModal");
const cancelEdit = document.getElementById("cancelEdit");
const saveEdit = document.getElementById("saveEdit");
const editorEl = document.getElementById("codeEditor");

let currentFile = "";
let cm = null;

document.querySelectorAll(".btn-icon.edit").forEach((btn) => {
    btn.addEventListener("click", async() => {
        currentFile = btn.dataset.file;
        title.textContent = "Editing: " + currentFile;

        try {
            const res = await fetch(
                "/api/read.php?file=" + encodeURIComponent(currentFile)
            );
            const text = await res.text();
            const data = JSON.parse(text);

            if (!res.ok || data.error) {
                throw new Error(data.error);
            }

            if (!cm) {
                cm = CodeMirror.fromTextArea(editorEl, {
                    lineNumbers: true,
                    theme: "dracula",
                    mode: "htmlmixed",
                    viewportMargin: Infinity,
                });
            }

            cm.setValue(data.content);
            cm.refresh();
            modal.classList.add("active");
        } catch (err) {
            alert("Error: " + err.message);
        }
    });
});

closeModal.onclick = () => {
    modal.classList.remove("active");
};

cancelEdit.onclick = () => {
    modal.classList.remove("active");
};

saveEdit.onclick = async() => {
    const content = cm.getValue();
    saveEdit.textContent = "Saving...";

    try {
        const res = await fetch("/api/save.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                file: currentFile,
                content: content,
            }),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            throw new Error(data.error);
        }

        modal.classList.remove("active");
        location.reload();
    } catch (err) {
        alert("Error: " + err.message);
    } finally {
        saveEdit.textContent = "Save";
    }
};