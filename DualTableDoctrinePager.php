<?php 
/**
 * DualTableDoctrinePager
 * a pager for combining the results of two tables
 *
 * @author Karol Sójko <karolsojko@gmail.com>
 */
class DualTableDoctrinePager extends sfDoctrinePager
{
  protected $queries = null;
 
  public function setQueries($queries)
  {
    $pagerQueries = array();
    foreach($queries as $key => $query)
    {
      $pagerQueries[$key]['query']  = $query;
      $pagerQueries[$key]['active'] = true;
    }
 
    $this->queries = $pagerQueries;
  }
 
  public function getCountQueries()
  {
    $queries = array();
 
    foreach($this->queries as $key => $query)
    {
      $queries[$key]['query'] = clone $query['query'];
      $queries[$key]['query']
        ->offset(0)
        ->limit(0);
    }
 
    return $queries;
  }
 
  public function init()
  {
    $this->results = null;
 
    $countQueries = $this->getCountQueries();
    $count = 0;
    $counts = array();
 
    // remebering counts for each table
    foreach($countQueries as $countQuery)
    {
      $currentCount = $countQuery['query']->count();
      $counts[] = $currentCount;
      $count += $currentCount;
    }
 
    $this->setNbResults($count);
 
    // reseting queries
    foreach($this->queries as &$query)
    {
      $query['query']
        ->offset(0)
        ->limit(0)
      ;
    }
 
    if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults())
    {
      $this->setLastPage(0);
    }
    else
    {
      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
 
      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));
 
      // set the queries
      if($counts[0] - $offset >= $this->getMaxPerPage())
      {
        // if offset is in the first table only
        foreach($this->queries as $key => &$query)
        {
          if($key == '0')
          {
            $query['active'] = true;
            $query['query']
              ->offset($offset)
              ->limit($this->getMaxPerPage());
          }
          else
          {
            $query['active'] = false;
          }
        }
      }
      else if($offset > $counts[0])
      {
        // if offset is in the second table only
        foreach($this->queries as $key => &$query)
        {
          if($key == '0')
          {
            $query['active'] = false;
          }
          else
          {
            $query['active'] = true;
            $query['query']
              ->offset($offset - $counts[0])
              ->limit($this->getMaxPerPage());
          }
        }
      }
      else
      {
        // if offset is in the first and second table
        foreach($this->queries as $key => &$query)
        {
          if($key == '0')
          {
            $query['active'] = true;
            $query['query']
              ->offset($offset)
              ->limit($counts[0] - $offset);
          }
          else
          {
            $query['active'] = true;
            $query['query']
              ->offset(0)
              ->limit($this->getMaxPerPage() - ($counts[0] - $offset));
          }
        }
      }
    }
  }
 
  public function getResults($hydrationMode = null)
  {
    $results = array();
 
    foreach($this->queries as $key => $query)
    {
      if($query['active'])
      {
        $results[] = $query['query']->execute(array(), $hydrationMode);
      }
      else
      {
        $results[] = array();
      }
    }
 
    return $results;
  }
}