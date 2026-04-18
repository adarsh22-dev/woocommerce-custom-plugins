<?php if (!defined('ABSPATH')) exit; ?>
<div class="wooai-wrap">
    <h1>🛍️ Product Assignments</h1>
    <p class="description">Assign products to show when users click quick action buttons (Max 6 per category)</p>
    
    <!-- Category Tabs -->
    <div class="wooai-tabs" style="margin: 20px 0;">
        <button class="tab-btn active" data-cat="bestselling">⭐ Bestselling</button>
        <button class="tab-btn" data-cat="recommended">👍 Recommended</button>
        <button class="tab-btn" data-cat="new_arrivals">⚡ New Arrivals</button>
        <button class="tab-btn" data-cat="offers">🏷️ Offers</button>
    </div>
    
    <!-- Currently Assigned -->
    <div class="wooai-card" style="background:#F0FDF4; border:2px solid #10B981; margin-bottom:20px;">
        <h2 style="margin:0 0 15px 0;">✅ Currently Assigned (<span id="assigned-count">0</span>/6)</h2>
        <div id="assigned-products">
            <p style="text-align:center; color:#6B7280;">Loading...</p>
        </div>
    </div>
    
    <!-- Product Selection -->
    <div class="wooai-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Select Products</h2>
            <input type="text" id="search" placeholder="🔍 Search products..." class="regular-text" style="max-width:300px;">
        </div>
        <div id="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:15px;">
            <p style="text-align:center; color:#6B7280; padding:40px;">Loading products...</p>
        </div>
        <div style="margin-top:20px; text-align:center;">
            <button id="save-btn" class="button button-primary button-large">💾 Save Assignments</button>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    let category = 'bestselling';
    let allProducts = [];
    let selectedIds = [];
    const MAX = 6;
    
    // Tab switching
    $('.tab-btn').click(function() {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        category = $(this).data('cat');
        loadAssignments();
    });
    
    // Search
    $('#search').on('input', function() {
        const search = $(this).val().toLowerCase();
        $('.product-card').each(function() {
            const name = $(this).data('name').toLowerCase();
            $(this).toggle(name.includes(search));
        });
    });
    
    // Load assigned products
    function loadAssignments() {
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_get_assignments',
            nonce: wooaiAdmin.nonce,
            category: category
        }, function(res) {
            if (res.success) {
                selectedIds = res.data.assignments || [];
                displayAssigned(res.data.products_data || []);
                loadProducts();
            }
        }).fail(function() {
            $('#assigned-products').html('<p style="color:#EF4444;">Error loading assignments</p>');
        });
    }
    
    // Display assigned products
    function displayAssigned(products) {
        $('#assigned-count').text(products.length);
        
        if (products.length === 0) {
            $('#assigned-products').html('<p style="text-align:center; color:#6B7280;">No products assigned yet</p>');
            return;
        }
        
        let html = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:12px;">';
        products.forEach(function(p) {
            html += `
                <div class="assigned-card" style="background:white; border:2px solid #10B981; border-radius:8px; padding:12px; position:relative;">
                    <button onclick="removeProduct(${p.id})" style="position:absolute; top:6px; right:6px; background:#EF4444; color:white; border:none; border-radius:50%; width:24px; height:24px; cursor:pointer; font-size:16px; line-height:1;">×</button>
                    ${p.image ? `<img src="${p.image}" style="width:100%; height:100px; object-fit:cover; border-radius:6px; margin-bottom:8px;">` : ''}
                    <div style="font-weight:600; font-size:13px; margin-bottom:4px;">${p.name}</div>
                    <div style="color:#10B981; font-weight:600; font-size:12px;">${p.price}</div>
                </div>
            `;
        });
        html += '</div>';
        $('#assigned-products').html(html);
    }
    
    // Remove product
    window.removeProduct = function(id) {
        if (!confirm('Remove this product?')) return;
        selectedIds = selectedIds.filter(i => i != id);
        saveAssignments();
    };
    
    // Load all products
    function loadProducts() {
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_get_products_list',
            nonce: wooaiAdmin.nonce
        }, function(res) {
            if (res.success) {
                allProducts = res.data.products || [];
                renderProducts();
            }
        }).fail(function() {
            $('#products-grid').html('<p style="color:#EF4444; text-align:center; padding:40px;">Error loading products</p>');
        });
    }
    
    // Render products
    function renderProducts() {
        const grid = $('#products-grid');
        
        if (allProducts.length === 0) {
            grid.html('<p style="text-align:center; color:#6B7280; padding:40px;">No products found</p>');
            return;
        }
        
        grid.empty();
        allProducts.forEach(function(p) {
            const isSelected = selectedIds.includes(p.id);
            const card = $(`
                <div class="product-card ${isSelected ? 'selected' : ''}" data-id="${p.id}" data-name="${p.name}">
                    <div class="check">${isSelected ? '✓' : ''}</div>
                    <img src="${p.image || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'120\'%3E%3Crect fill=\'%23f3f4f6\' width=\'200\' height=\'120\'/%3E%3C/svg%3E'}" />
                    <div class="info">
                        <div class="name">${p.name}</div>
                        <div class="price">${p.price}</div>
                    </div>
                </div>
            `);
            
            card.click(function() {
                const id = $(this).data('id');
                
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected').find('.check').text('');
                    selectedIds = selectedIds.filter(i => i !== id);
                } else {
                    if (selectedIds.length >= MAX) {
                        alert(`Maximum ${MAX} products per category!`);
                        return;
                    }
                    $(this).addClass('selected').find('.check').text('✓');
                    selectedIds.push(id);
                }
            });
            
            grid.append(card);
        });
    }
    
    // Save
    $('#save-btn').click(function() {
        saveAssignments();
    });
    
    function saveAssignments() {
        const btn = $('#save-btn');
        btn.prop('disabled', true).text('Saving...');
        
        $.post(wooaiAdmin.ajax_url, {
            action: 'wooai_save_assignments',
            nonce: wooaiAdmin.nonce,
            category: category,
            products: selectedIds,
            mode: 'products'
        }, function(res) {
            btn.prop('disabled', false).text('💾 Save Assignments');
            if (res.success) {
                alert('✅ Saved successfully!');
                loadAssignments();
            } else {
                alert('❌ Error saving');
            }
        }).fail(function() {
            btn.prop('disabled', false).text('💾 Save Assignments');
            alert('❌ Error saving');
        });
    }
    
    // Initial load
    loadAssignments();
});
</script>

<style>
.wooai-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.wooai-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 10px 20px;
    border: 2px solid #E5E7EB;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.tab-btn:hover {
    border-color: #7C3AED;
    background: #F5F3FF;
}

.tab-btn.active {
    border-color: #7C3AED;
    background: #7C3AED;
    color: white;
}

.product-card {
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.product-card:hover {
    border-color: #7C3AED;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(124,58,237,0.2);
}

.product-card.selected {
    border-color: #7C3AED;
    background: #F5F3FF;
}

.product-card .check {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    background: #7C3AED;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.product-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 10px;
}

.product-card .name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #1F2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-card .price {
    color: #7C3AED;
    font-weight: 600;
    font-size: 13px;
}
</style>
