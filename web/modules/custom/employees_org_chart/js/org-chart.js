/**
 * @file
 * Draws org charts from drupalSettings.employeesOrgChart using d3-org-chart.
 *
 * Opens centered on the person in ?focus=<user id>, or else on the viewer.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  const NODE_WIDTH = 250;
  const NODE_HEIGHT = 108;
  const HIGHLIGHT = '#164c9c';
  const LINK = '#b8c0cc';

  /**
   * Returns up to two initials for people without a photo.
   */
  function initials(name) {
    return name
      .split(/\s+/)
      .filter(Boolean)
      .map((part) => part[0])
      .filter((_, i, all) => i === 0 || i === all.length - 1)
      .join('')
      .toUpperCase();
  }

  /**
   * Returns the direct-report and org-size line for a card, or ''.
   *
   * "In org" counts everyone under the person, at any level. It is left out
   * when it equals the direct reports (nobody below has reports).
   */
  function reportCounts(person) {
    if (!person.directReports) {
      return '';
    }
    const esc = Drupal.checkPlain;
    if (person.teamSize <= person.directReports) {
      return `<span class="eoc-card__reports">${esc(Drupal.formatPlural(person.directReports, '1 direct report', '@count direct reports'))}</span>`;
    }
    const description = Drupal.t('@direct, @total in their org', {
      '@direct': Drupal.formatPlural(person.directReports, '1 direct report', '@count direct reports'),
      '@total': Drupal.formatPlural(person.teamSize, '1 person', '@count people'),
    });
    const text = Drupal.t('@direct direct · @total in org', {
      '@direct': person.directReports.toLocaleString(),
      '@total': person.teamSize.toLocaleString(),
    });
    return `<span class="eoc-card__reports" title="${esc(description)}">${esc(text)}</span>`;
  }

  /**
   * Builds the HTML for one card. All text is escaped.
   */
  function nodeContent(d) {
    const person = d.data;
    const esc = Drupal.checkPlain;
    if (person.virtual) {
      const subtitle = person.title ? `<span class="eoc-card__title">${esc(person.title)}</span>` : '';
      return `<div class="eoc-card eoc-card--virtual" style="width:${d.width}px;height:${d.height}px">
        <div class="eoc-card__text"><span class="eoc-card__name">${esc(person.name)}</span>${subtitle}</div>
      </div>`;
    }
    const classes = ['eoc-card'];
    if (person._highlighted) {
      classes.push('eoc-card--highlighted');
    }
    const avatar = person.image
      ? `<img class="eoc-card__avatar" src="${esc(person.image)}" alt="" loading="lazy">`
      : `<span class="eoc-card__avatar eoc-card__avatar--initials" aria-hidden="true">${esc(initials(person.name))}</span>`;
    const reports = reportCounts(person);
    return `<div class="${classes.join(' ')}" style="width:${d.width}px;height:${d.height}px">
      ${avatar}
      <div class="eoc-card__text">
        <a class="eoc-card__name" href="${esc(person.url)}">${esc(person.name)}</a>
        <span class="eoc-card__title" title="${esc(person.title || '')}">${esc(person.title || '')}</span>
        ${person.group && person.group !== person.title ? `<span class="eoc-card__group" title="${esc(person.group)}">${esc(person.group)}</span>` : ''}
        ${reports}
      </div>
    </div>`;
  }

  /**
   * Shows the first `levels` rows of real people.
   *
   * In d3-org-chart, `_expanded` means "this node is visible" (its ancestors
   * get opened), not "its children are visible". Virtual boxes don't count as
   * a level, and the children of a box flagged `collapsed` stay hidden.
   */
  function setInitialExpansion(nodes, levels) {
    const byId = new Map(nodes.map((node) => [node.id, node]));
    const state = new Map();
    const stateOf = (node) => {
      if (!state.has(node.id)) {
        const parent = node.parentId ? byId.get(node.parentId) : null;
        const parentState = parent ? stateOf(parent) : { depth: 0, hidden: false };
        state.set(node.id, {
          depth: parentState.depth + (node.virtual ? 0 : 1),
          hidden: parentState.hidden || Boolean(parent && parent.collapsed),
        });
      }
      return state.get(node.id);
    };
    nodes.forEach((node) => {
      const { depth, hidden } = stateOf(node);
      node._expanded = !hidden && (node.virtual || depth <= levels);
    });
  }

  /**
   * Clears search highlighting and centering flags left by earlier searches.
   */
  function clearFocus(nodes) {
    nodes.forEach((node) => {
      node._highlighted = false;
      node._upToTheRootHighlighted = false;
      node._centered = false;
    });
  }

  /**
   * Fills the search datalist and returns a label => node id lookup.
   */
  function buildSearchIndex(nodes, datalist) {
    const index = new Map();
    const fragment = document.createDocumentFragment();
    nodes
      .filter((node) => !node.virtual)
      .sort((a, b) => a.name.localeCompare(b.name))
      .forEach((node) => {
        const details = [node.title, node.group !== node.title ? node.group : '']
          .filter(Boolean)
          .join(' · ');
        const base = details ? `${node.name} – ${details}` : node.name;
        let label = base;
        for (let n = 2; index.has(label); n++) {
          label = `${base} (${n})`;
        }
        index.set(label, node.id);
        const option = document.createElement('option');
        option.value = label;
        fragment.appendChild(option);
      });
    datalist.appendChild(fragment);
    return index;
  }

  Drupal.behaviors.employeesOrgChart = {
    attach(context) {
      once('employees-org-chart', '[data-org-chart-id]', context).forEach((element) => {
        const settings = (drupalSettings.employeesOrgChart || {})[element.dataset.orgChartId];
        if (!settings || !window.d3 || !window.d3.OrgChart) {
          return;
        }
        const nodes = settings.nodes;
        const nodeIds = new Set(nodes.map((node) => node.id));
        setInitialExpansion(nodes, settings.levels);

        const chart = new window.d3.OrgChart()
          .container(element.querySelector('.employees-org-chart__canvas'))
          .data(nodes)
          .svgHeight(settings.height)
          .compact(settings.compact)
          .nodeWidth(() => NODE_WIDTH)
          .nodeHeight(() => NODE_HEIGHT)
          .childrenMargin(() => 48)
          .siblingsMargin(() => 16)
          .neighbourMargin(() => 32)
          .compactMarginBetween(() => 16)
          .compactMarginPair(() => 48)
          .nodeContent(nodeContent)
          .nodeUpdate(function (d) {
            window.d3.select(this).select('.node-rect').attr('stroke', 'none');
          })
          .linkUpdate(function (d) {
            const highlighted = d.data._upToTheRootHighlighted;
            window.d3.select(this)
              .attr('stroke', highlighted ? HIGHLIGHT : LINK)
              .attr('stroke-width', highlighted ? 3 : 1);
            if (highlighted) {
              window.d3.select(this).raise();
            }
          });

        // Compact mode packs a parent's childless children into two columns.
        // Under the virtual top box those are the collapsed group boxes, and
        // packing them next to the main chart's root makes them overlap it, so
        // skip compact packing there.
        const calculateCompactFlexDimensions = chart.calculateCompactFlexDimensions;
        chart.calculateCompactFlexDimensions = function (root) {
          calculateCompactFlexDimensions.call(this, root);
          if (root.data.virtual && root.children) {
            root.children.forEach((child) => {
              child.flexCompactDim = null;
              child.firstCompact = null;
              child.firstCompactNode = null;
            });
          }
        };

        const focus = (id) => {
          clearFocus(nodes);
          chart.setUpToTheRootHighlighted(id).setHighlighted(id).render();
        };

        const focusId = [
          new URLSearchParams(window.location.search).get('focus'),
          drupalSettings.user ? String(drupalSettings.user.uid) : null,
        ].find((id) => id && nodeIds.has(id));
        // With several top-level people, the whole top row is too wide to
        // read, so zoom to the top box, the main chart (placed in the middle
        // of the row) and its nearest neighbours. "Fit to screen" still shows
        // everything.
        const showOverview = (animate) => {
          const { root } = chart.getChartState();
          if (!root.data.virtual || !root.children) {
            chart.fit({ animate });
            return;
          }
          const row = root.children;
          const middle = row.findIndex((child) => !child.data.virtual && !child.data.collapsed);
          const main = row[middle] || row[0];
          const neighbours = row.slice(Math.max(0, middle - 2), middle + 3);
          chart.fit({ animate, nodes: [root, ...neighbours, ...main.descendants()] });
        };

        // The first render builds the tree that the focus helpers search.
        chart.render();
        if (focusId) {
          focus(focusId);
        }
        else {
          showOverview(false);
        }

        const search = element.querySelector('[data-org-chart-search]');
        const index = buildSearchIndex(nodes, element.querySelector('datalist'));
        // Picking a suggestion fires "input" right away, while "change" waits
        // for Enter or blur. Focus as soon as the box holds an exact
        // suggestion, and only once per person.
        let searchedId = null;
        const onSearch = () => {
          const id = index.get(search.value) || null;
          if (id && id !== searchedId) {
            focus(id);
          }
          searchedId = id;
        };
        search.addEventListener('input', onSearch);
        search.addEventListener('change', onSearch);

        element.querySelector('[data-org-chart-action="fit"]').addEventListener('click', () => chart.fit());
        element.querySelector('[data-org-chart-action="collapse"]').addEventListener('click', () => {
          clearFocus(nodes);
          setInitialExpansion(nodes, settings.levels);
          search.value = '';
          searchedId = null;
          chart.render();
          showOverview(true);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
