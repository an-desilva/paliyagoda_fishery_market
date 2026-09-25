/* assets/js/pos_calculator.js */
/* Rapid POS Calculator & Keyboard Shortcuts */

let posItems = [];

document.addEventListener('DOMContentLoaded', () => {
    const tagInput = document.getElementById('tag_input');
    if (tagInput) {
        tagInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupAndAddTag();
            }
        });
    }

    // Global keyboard listener
    document.addEventListener('keydown', (e) => {
        // Shift+Enter to trigger checkout
        if (e.shiftKey && e.key === 'Enter') {
            e.preventDefault();
            const posForm = document.getElementById('posForm');
            if (posForm && posItems.length > 0) {
                posForm.submit();
            }
        }
    });
});

async function lookupAndAddTag() {
    const tagInput = document.getElementById('tag_input');
    const errorMsg = document.getElementById('tag-error-msg');
    const errorText = document.getElementById('tag-error-text');
    
    if (!tagInput || !tagInput.value.trim()) return;

    const tagCode = tagInput.value.trim().toUpperCase();
    errorMsg.classList.add('hidden');

    // Check if already added to cart
    if (posItems.some(item => item.tag_code === tagCode)) {
        errorText.textContent = `Tag ${tagCode} is already added to this bill.`;
        errorMsg.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch(`api/get_fish_by_tag.php?tag=${encodeURIComponent(tagCode)}`);
        const data = await response.json();

        if (data.success && data.fish) {
            const fish = data.fish;
            // Add to items array
            posItems.push({
                fish_id: fish.id,
                tag_code: fish.tag_code,
                species: fish.species,
                net_weight: parseFloat(fish.net_weight),
                grade: fish.grade,
                unit_price: 1850.00 // Default estimated auction price per kg
            });

            tagInput.value = '';
            renderPOSTable();
        } else {
            errorText.textContent = data.message || 'Tag not found.';
            errorMsg.classList.remove('hidden');
        }
    } catch (err) {
        errorText.textContent = 'Network or API error occurred.';
        errorMsg.classList.remove('hidden');
    }
}

function removePOSItem(index) {
    posItems.splice(index, 1);
    renderPOSTable();
}

function updateItemUnitPrice(index, value) {
    const price = parseFloat(value) || 0;
    posItems[index].unit_price = price;
    renderPOSTable(false); // Don't re-render input focus
}

function renderPOSTable(fullRender = true) {
    const tbody = document.getElementById('posTableBody');
    const emptyRow = document.getElementById('emptyRow');
    if (!tbody) return;

    if (posItems.length === 0) {
        tbody.innerHTML = `
            <tr id="emptyRow">
                <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                    No fish items added to current bill yet.<br>Scan or type a Tag Code above to start.
                </td>
            </tr>
        `;
        calculateGrandTotal();
        return;
    }

    if (fullRender) {
        tbody.innerHTML = '';
        posItems.forEach((item, index) => {
            const lineTotal = item.net_weight * item.unit_price;
            const tr = document.createElement('tr');
            tr.className = 'pos-table-row border-b border-slate-800/60 transition-colors';
            tr.innerHTML = `
                <td class="p-3 font-mono font-bold text-cyan-300">
                    ${item.tag_code}
                    <input type="hidden" name="items[${index}][fish_id]" value="${item.fish_id}">
                </td>
                <td class="p-3 text-slate-200">
                    ${item.species}
                    <span class="block text-[10px] text-slate-400 uppercase font-semibold">${item.grade}</span>
                </td>
                <td class="p-3 text-right font-mono font-bold text-emerald-400">${item.net_weight.toFixed(2)} kg</td>
                <td class="p-3 text-right">
                    <input type="number" step="10" name="items[${index}][unit_price]" value="${item.unit_price}"
                        oninput="updateItemUnitPrice(${index}, this.value)"
                        class="w-28 py-1 px-2 border border-slate-700 rounded bg-slate-950 text-amber-300 font-mono font-bold text-right text-xs focus:outline-none focus:ring-1 focus:ring-amber-500">
                </td>
                <td class="p-3 text-right font-mono font-extrabold text-white" id="line-total-${index}">
                    Rs. ${lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                </td>
                <td class="p-3 text-center">
                    <button type="button" onclick="removePOSItem(${index})" class="p-1 text-slate-500 hover:text-red-400 transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        // Update totals only
        posItems.forEach((item, index) => {
            const lineTotal = item.net_weight * item.unit_price;
            const lineElem = document.getElementById(`line-total-${index}`);
            if (lineElem) {
                lineElem.textContent = `Rs. ${lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        });
    }

    calculateGrandTotal();
}

function calculateGrandTotal() {
    let subtotal = 0;
    posItems.forEach(item => {
        subtotal += item.net_weight * item.unit_price;
    });

    const handlingFee = parseFloat(document.getElementById('handling_fee')?.value) || 0;
    const cuttingFee = parseFloat(document.getElementById('cutting_fee')?.value) || 0;
    const grandTotal = subtotal + handlingFee + cuttingFee;

    const countElem = document.getElementById('pos-items-count');
    const subtotalElem = document.getElementById('pos-subtotal-display');
    const grandTotalElem = document.getElementById('pos-grand-total');

    if (countElem) countElem.textContent = posItems.length;
    if (subtotalElem) subtotalElem.textContent = `Rs. ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (grandTotalElem) grandTotalElem.textContent = `Rs. ${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // Check Credit Limit Warning
    checkCreditLimitWarning(grandTotal);
}

async function onBuyerChange(buyerId) {
    if (!buyerId) {
        document.getElementById('buyer-credit-limit').textContent = 'Rs. 0.00';
        document.getElementById('buyer-credit-balance').textContent = 'Rs. 0.00';
        document.getElementById('buyer-credit-available').textContent = 'Rs. 0.00';
        return;
    }

    try {
        const response = await fetch(`api/search_buyers.php?id=${buyerId}`);
        const data = await response.json();
        if (data.success && data.buyer) {
            const b = data.buyer;
            document.getElementById('buyer-credit-limit').textContent = `Rs. ${parseFloat(b.credit_limit).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('buyer-credit-balance').textContent = `Rs. ${parseFloat(b.current_credit_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('buyer-credit-available').textContent = `Rs. ${parseFloat(b.available_credit).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            
            calculateGrandTotal();
        }
    } catch (e) {
        console.error("Buyer lookup error:", e);
    }
}

function checkCreditLimitWarning(grandTotal) {
    const paymentMode = document.querySelector('input[name="payment_mode"]:checked')?.value;
    const warningText = document.getElementById('credit-warning-text');
    const btnCheckout = document.getElementById('btn-checkout');

    if (!warningText) return;

    if (paymentMode === 'credit') {
        const availableText = document.getElementById('buyer-credit-available')?.textContent.replace(/[^0-9.]/g, '') || '0';
        const availableCredit = parseFloat(availableText) || 0;

        if (grandTotal > availableCredit && availableCredit > 0) {
            warningText.classList.remove('hidden');
        } else {
            warningText.classList.add('hidden');
        }
    } else {
        warningText.classList.add('hidden');
    }
}
