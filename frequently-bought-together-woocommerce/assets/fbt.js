jQuery(document).ready(function($) {
    const formatPrice = (price) => {
        const { decimals, decimal_sep, thousand_sep, currency, position } = fbt_data;
        price = Number(price).toFixed(decimals).split('.');
        price[0] = price[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand_sep);
        price = price.join(decimal_sep);

        return {
            left: `${currency}${price}`,
            right: `${price}${currency}`,
            'left_space': `${currency} ${price}`,
            'right_space': `${price} ${currency}`
        }[position] || price;
    };

    const updateTotal = () => {
        const total = $('.fbt-product.main').data('price') || 0;
        const checkedPrice = $('.fbt-checkbox:checked').toArray()
            .reduce((sum, cb) => sum + ($(cb).closest('.fbt-product').data('price') || 0), 0);
        $('.fbt-total-price').text(formatPrice(total + checkedPrice));
    };

    // Initial total
    updateTotal();

    // Update total on checkbox change
    $('.fbt-checkbox').on('change', updateTotal);

    // Add to cart
    $('.fbt-add-to-cart').on('click', (e) => {
        e.preventDefault();
        const main_id = $(e.target).data('main-id');
        const products = $('.fbt-checkbox:checked').map((i, el) => $(el).closest('.fbt-product').data('id')).get();

        $.post(fbt_data.ajax_url, {
            action: 'fbt_add_to_cart',
            main_id,
            products
        }).done((response) => {
            if (response.success) {
                alert(response.data.message);
                $(document.body).trigger('wc_fragment_refresh');
            } else {
                alert('Error adding to cart.');
            }
        }).fail(() => alert('Error adding to cart.'));
    });

    // Slider functionality
    if (window.matchMedia('(max-width: 768px)').matches) {
        const $products = $('.fbt-products');
        let currentIndex = parseInt($products.data('slider-index')) || 0;

        const updateSlider = () => {
            const $items = $products.find('.fbt-product');
            $products.css('transform', `translateX(-${currentIndex * 100}%)`);
            updateTotal(); // Update total based on visible products
        };

        $('.fbt-products').on('scroll', () => {
            const scrollLeft = $products.scrollLeft();
            const itemWidth = $products.find('.fbt-product').outerWidth(true);
            currentIndex = Math.round(scrollLeft / itemWidth);
            $products.data('slider-index', currentIndex);
            updateTotal();
        });

        $(window).on('resize', updateSlider);
        updateSlider();
    }
});